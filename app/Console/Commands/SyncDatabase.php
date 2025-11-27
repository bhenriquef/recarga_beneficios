<?php

namespace App\Console\Commands;

use App\Exports\IfoodExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VRExport;
use App\Services\SolidesService;
use App\Services\VrBeneficiosService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\{
    Employee,
    Company,
    Benefit,
    EmployeeBenefit,
    Absenteeism,
    Holiday,
    Credit,
    Workday,
    EmployeesBenefitsMonthly,
    EmployeesBenefits
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SyncDatabase extends Command
{
    protected $signature = 'sync:database {--stream}';
    protected $description = 'Sincroniza o banco de dados com os dados da solides e vr';

    /* --------------------------------------------------------------
     * Helpers para progress/log
     * -------------------------------------------------------------- */
    private function updateProgress(float $progress, ?string $eta = null): void
    {
        // Garante que fica entre 0 e 100
        $progress = number_format(max(0, min(100, $progress)), 2);
        $isStream = $this->option('stream');

        if ($isStream) {
            Cache::put('sync_progress', $progress);

            if ($eta !== null) {
                Cache::put('sync_eta', $eta);
            }
        }
    }

    private function addLog(string $message): void
    {
        $isStream = $this->option('stream');

        if ($isStream) {
            $logs = Cache::get('sync_logs', []);
            $logs[] = "[" . now()->format('H:i:s') . "] " . $message;
            Cache::put('sync_logs', $logs);

            $this->info($message);
        }
    }

    private function markFinished(): void
    {
        Cache::put('sync_finished', true);
    }

    public function handle(SolidesService $solides, VrBeneficiosService $vr)
    {
        // Reset do estado para o front
        // ini_set('memory_limit', '512M');
        // for($u = 0; $u < 6; $u++){
        //     echo "teste: ".$u;
            Log::info('Entrou no handle() do sync:database');

            $isStream = $this->option('stream');

            if ($isStream) {
                Cache::put('sync_progress', 0);
                Cache::put('sync_logs', []);
                Cache::put('sync_eta', null);
                Cache::put('sync_finished', false);
                Cache::put('sync_error', null);
            }

            $this->addLog("Começando a sincronização dos dados");

            $startTime = microtime(true);

            // Pesos das etapas
            $weightCompanies  = 5;   // 5%
            $weightDates      = 5;   // 5%
            $weightEmployees  = 70;  // 70%
            $weightHolidays   = 10;  // 10%
            $weightAbsences   = 10;  // 10%
            $baseProgress     = 0;   // acumula o que já foi feito antes da etapa atual

            // Função para calcular ETA e atualizar progresso
            $updateStepProgress = function (float $currentProgress) use ($startTime) {
                $etaString = null;

                if ($currentProgress > 0) {
                    $elapsed = microtime(true) - $startTime; // segundos
                    // Estimativa do tempo total usando regra de 3
                    $totalEstimated = $elapsed * (100 / $currentProgress);
                    $remaining = max(0, $totalEstimated - $elapsed);
                    $etaString = gmdate("i:s", (int) round($remaining));
                }

                $this->updateProgress($currentProgress, $etaString);
            };

            try {
                /* ------------------------------------------
                * ETAPA 1 — EMPRESAS
                * ------------------------------------------ */
                $this->addLog("Pegando empresas");
                $empresas = $solides->getEmpresas();

                $this->addLog("Cadastrando empresas");

                // Transação apenas desta etapa (pequena)
                DB::transaction(function () use ($empresas) {
                    foreach ($empresas as $emp) {
                        Company::updateOrCreate(
                            ['cod' => $emp['id'], 'from' => 'Solides'],
                            [
                                'name' => $emp['socialReason'],
                                'company' => $emp['descriptionName'],
                                'cnpj' => $emp['cnpj'],
                            ]
                        );
                    }
                });

                $baseProgress += $weightCompanies;
                $updateStepProgress($baseProgress);

                /* ------------------------------------------
                * ETAPA 2 — CALCULO DAS DATAS
                * ------------------------------------------ */
                $this->addLog("Configurando datas de referência");

                $today = Carbon::today();
                $base  = $today->day >= 16 ? $today->copy() : $today->subMonth()->copy();

                $inicio = $base->copy()->subMonth()->day(16)->startOfDay();
                $fim    = $base->copy()->day(15)->endOfDay();

                $diasUteis = calcularDiasUteisComSabado(
                    $base->copy()->addMonth()->startOfMonth(),
                    $base->copy()->addMonth()->endOfMonth()
                );

                $diasUteisMesPassado = calcularDiasUteisComSabado(
                    $base->copy()->startOfMonth(),
                    $base->copy()->endOfMonth()
                );

                $baseProgress += $weightDates;
                $updateStepProgress($baseProgress);

                /* ------------------------------------------
                * ETAPA 3 — FUNCIONÁRIOS (70% do progresso)
                * ------------------------------------------ */
                $this->addLog("Pegando funcionários");
                $funcionarios = $solides->getFuncionariosAtivos();
                $totalEmployees = count($funcionarios);

                $this->addLog("Inativando funcionários atuais");
                Employee::where('active', true)->update(['active' => false]);

                $companies = Company::all()
                    ->keyBy('cod')
                    ->map(fn($item) => [
                        'cnpj' => $item->cnpj,
                        'id'   => $item->id,
                    ])
                    ->toArray();

                $this->addLog("Cadastrando funcionários");
                $this->addLog("Número de dados a serem sincronizados: " . $totalEmployees);

                $inativados = 0;
                $processedEmployees = 0;

                foreach ($funcionarios as $f) {
                    $processedEmployees++;

                    if ($f['fired'] == true) {
                        $inativados++;
                        // Atualiza progresso proporcional mesmo pulando
                        if ($totalEmployees > 0) {
                            $employeesProgress = ($processedEmployees / $totalEmployees) * $weightEmployees;
                            $updateStepProgress($baseProgress + $employeesProgress);
                        }
                        continue;
                    }

                    // === LÓGICA ORIGINAL DO FUNCIONÁRIO ===

                    $birthday      = isset($f['birthDate']) ? Carbon::createFromTimestampMs($f['birthDate']) : null;
                    $admissionDate = isset($f['admissionDate']) ? Carbon::createFromTimestampMs($f['admissionDate']) : null;

                    $employee = Employee::updateOrCreate(
                        ['cpf' => preg_replace('/\D/', '', $f['cpf'])],
                        [
                            'active'        => true,
                            'full_name'     => $f['name'],
                            'email'         => $f['email'] ?? null,
                            'rg'            => $f['rg'] ?? null,
                            'birthday'      => $birthday ? $birthday->toDateTimeString() : null,
                            'cod_solides'   => $f['id'],
                            'address'       => null,
                            'company_id'    => $companies[$f['company']['id']]['id'] ?? null,
                            'admission_date'=> $admissionDate,
                        ]
                    );

                    $ferias = 0;
                    $diasTrabalhadosMesPassado = $diasUteisMesPassado;
                    $diasTrabalhados = $diasUteis;

                    if ($f['admissionDate'] < $fim->valueOf()) {
                        $diasUteisMesPassadoCalc = $diasUteisMesPassado;

                        if ($f['admissionDate'] > $inicio->valueOf()) {
                            $admissionDate = Carbon::createFromTimestampMs($f['admissionDate']);
                            $diasUteisMesPassadoCalc = calcularDiasUteisComSabado(
                                $admissionDate,
                                $base->copy()->day(15)
                            );
                        }

                        $array_dias_trabalhados = $solides->getDiasTrabalhados($f['id'], $inicio->valueOf(), $fim->valueOf());

                        if (empty($array_dias_trabalhados)) {
                            $inativados++;
                            $this->addLog("Inativado por não trabalhar no último mês: " . $employee->full_name);
                            $employee->update(['active' => false]);

                            if ($totalEmployees > 0) {
                                $employeesProgress = ($processedEmployees / $totalEmployees) * $weightEmployees;
                                $updateStepProgress($baseProgress + $employeesProgress);
                            }
                            continue;
                        }

                        $isInactive = false;
                        $lastDate = null;
                        $diasTrabalhadosMesPassado = 0;

                        foreach ($array_dias_trabalhados as $index => $data) {
                            $currentDate = Carbon::createFromTimestampMs($data['date']);
                            $diferenca = $currentDate->diffInDays($lastDate);

                            if ($diferenca == 0)
                                continue;

                            $diasTrabalhadosMesPassado++;

                            if ($lastDate && $diferenca > 7) {
                                $isInactive = true;
                                break;
                            }

                            $lastDate = $currentDate;
                        }

                        $diferencaDiasMesPassado = $diasUteisMesPassadoCalc - $diasTrabalhadosMesPassado;
                        $diasTrabalhados = $diasUteis - $diferencaDiasMesPassado;

                        if ($diasTrabalhados > $diasUteis)
                            $diasTrabalhados = $diasUteis;

                        if ($isInactive || $diasTrabalhados < ($diasUteis / 2)) {
                            $employee->update(['active' => false]);
                            $inativados++;
                            $this->addLog("Inativado por faltas: " . $employee->full_name);

                            if ($totalEmployees > 0) {
                                $employeesProgress = ($processedEmployees / $totalEmployees) * $weightEmployees;
                                $updateStepProgress($baseProgress + $employeesProgress);
                            }
                            continue;
                        } else {
                            $employee->update(['active' => true]);
                        }
                    }

                    $EmployeesBenefits = EmployeesBenefits::where('employee_id', $employee->id)->get();
                    $EmployeesBenefitsMonthly = [];

                    foreach ($EmployeesBenefits as $empb) {
                        $valueBenefit = $diasTrabalhados * $empb['qtd'] * $empb['value'];
                        // $EmployeesBenefitsMonthly[] = [
                        //     'employee_benefit_id' => $empb['id'],
                        //     'value'               => $empb['value'],
                        //     'qtd'                 => $empb['qtd'],
                        //     'work_days'           => $diasTrabalhados,
                        //     'total_value'         => $valueBenefit,
                        //     'paid'                => true,
                        //     'date'                => $base->copy()->day(1)->format('Y-m-d'),
                        // ];

                        EmployeesBenefitsMonthly::updateOrCreate(
                            [
                                'employee_benefit_id' => $empb['id'],
                                'date' => $base->copy()->day(1)->format('Y-m-d'),
                            ],
                            [
                                'value' => $empb['value'],
                                'qtd' => $empb['qtd'],
                                'work_days' => $diasTrabalhados,
                                'total_value' => $valueBenefit,
                                'paid' => true,
                            ]
                        );
                    }

                    // EmployeesBenefitsMonthly::upsert(
                    //     $EmployeesBenefitsMonthly,
                    //     ['employee_benefit_id', 'date'],
                    //     ['value', 'qtd', 'work_days', 'total_value', 'paid']
                    // );

                    Workday::updateOrCreate([
                        'employee_id' => $employee->id,
                        'date'        => $base->copy()->addMonth()->day(1)->format('Y-m-d'),
                    ], [
                        'business_days' => $diasUteis,
                        'calc_days'     => $diasTrabalhados,
                        'worked_days'   => $diasTrabalhadosMesPassado,
                        'start_date'    => $inicio->format('Y-m-d'),
                        'end_date'      => $fim->format('Y-m-d')
                    ]);

                    // Atualiza progresso proporcional dos funcionários
                    if ($totalEmployees > 0) {
                        $employeesProgress = ($processedEmployees / $totalEmployees) * $weightEmployees;
                        $updateStepProgress($baseProgress + $employeesProgress);
                    }
                }

                // Finaliza etapa de funcionários
                $baseProgress += $weightEmployees;
                $updateStepProgress($baseProgress);

                /* ------------------------------------------
                * ETAPA 4 — FÉRIAS
                * ------------------------------------------ */
                $this->addLog("Pegando férias da API");
                $authToken = $solides->apiTangerinoAuth();
                $array_holidays = $solides->getHolidays($authToken);
                $employeesMap = Employee::pluck('id', 'email')->toArray();

                if ($array_holidays != []) {
                    $holidays = $array_holidays['list'];

                    for ($i = 1; $i <= $array_holidays['totalPages']; $i++) {

                        // Transação pequena por página de férias
                        DB::transaction(function () use ($holidays, $employeesMap) {
                            foreach ($holidays as $holiday) {
                                if (isset($employeesMap[$holiday['employee']['email']])) {
                                    $employee_id = $employeesMap[$holiday['employee']['email']];
                                    Holiday::updateOrCreate([
                                        'employee_id' => $employee_id,
                                        'start_date'  => $holiday['startDate'],
                                        'end_date'    => $holiday['endDate'],
                                    ]);
                                }
                            }
                        });

                        if ($i == $array_holidays['totalPages']) {
                            break;
                        }

                        $holidays = $solides->getHolidays($authToken, $i + 1)['list'];
                    }
                }

                $baseProgress += $weightHolidays;
                $updateStepProgress($baseProgress);

                /* ------------------------------------------
                * ETAPA 5 — AUSÊNCIAS
                * ------------------------------------------ */
                $this->addLog("Pegando ausências futuras API da Tangerino");
                $lastUpdate = $base->copy()->subMonth(6)->startOfDay()->valueOf();
                $array_absenteeism = $solides->getAbsenteeism($lastUpdate);

                if ($array_absenteeism != []) {
                    $ausencias = Absenteeism::pluck('id', 'solides_id')->toArray();
                    $absenteeisms = $array_absenteeism['content'];

                    for ($i = 1; $i <= $array_absenteeism['totalPages']; $i++) {
                        $this->addLog("Ausências página: " . $i);

                        DB::transaction(function () use ($absenteeisms, $ausencias, $employeesMap) {
                            foreach ($absenteeisms as $ab) {
                                if (isset($ausencias[$ab['id']])) {
                                    continue;
                                }

                                if (isset($employeesMap[$ab['employeeDTO']['email']])) {
                                    $employee_id = $employeesMap[$ab['employeeDTO']['email']];
                                    Absenteeism::updateOrCreate([
                                        'solides_id' => $ab['id'],
                                    ], [
                                        'employee_id' => $employee_id,
                                        'start_date'  => Carbon::createFromTimestampMs($ab['startDate']),
                                        'end_date'    => Carbon::createFromTimestampMs($ab['endDate']),
                                        'reason'      => $ab['adjustmentReasonDTO']['description'],
                                    ]);
                                }
                            }
                        });

                        if ($i == $array_absenteeism['totalPages']) {
                            break;
                        }

                        $absenteeisms = $solides->getAbsenteeism($lastUpdate, $i + 1)['content'];
                    }
                }

                $baseProgress += $weightAbsences;
                $updateStepProgress($baseProgress);

                /* ------------------------------------------
                * FINALIZAÇÃO
                * ------------------------------------------ */
                $this->addLog("Dados sincronizados com sucesso.");
                $this->addLog('Número de funcionários inativados: ' . $inativados);

                $this->updateProgress(100, "00:00");
                $this->markFinished();

            } catch (\Throwable $e) {
                $this->addLog("❌ Erro ao sincronizar: " . $e->getMessage());
                Log::error('Erro no SyncDatabase: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                Cache::put('sync_error', $e->getMessage());
                $this->updateProgress(100, "00:00");
                $this->markFinished();
            }
            // sleep(60);
        // }
    }
}
