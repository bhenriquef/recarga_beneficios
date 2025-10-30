<?php

namespace App\Console\Commands;

use App\Exports\IfoodExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VRExport;
use App\Services\SolidesService;
use App\Services\VrBeneficiosService;
use Illuminate\Support\Facades\Log;
// use App\Mail\EmployeeBenefitsReport;
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

class SyncDatabase extends Command
{
    protected $signature = 'sync:database';
    protected $description = 'Sincroniza o banco de dados com os dados da solides e vr';

    public function handle(SolidesService $solides, VrBeneficiosService $vr)
    {
       DB::beginTransaction();
        $this->info("Começando a sincronização dos dados");
        try {
            // cadastrar empresas

            $this->info("Pegando empresas");
            $empresas = $solides->getEmpresas();
            $this->info("Cadastrando empresas");
            foreach($empresas as $emp){
                Company::updateOrCreate(
                    ['cod' => $emp['id'], 'from' => 'Solides'],
                    [
                        'name' => $emp['socialReason'],
                        'company' => $emp['descriptionName'],
                        'cnpj' => $emp['cnpj'],
                        // 'user_id' => Auth::user()->id,
                    ]
                );
            }

            // Períodos de referência
            $inicio = Carbon::now()->subMonth()->day(16)->startOfDay();
            $fim = Carbon::now()->day(15)->endOfDay();

            // Dias úteis de 16/mês atual até 15/mês seguinte
            $diasUteis = calcularDiasUteisComSabado(
                Carbon::now()->day(16),
                Carbon::now()->addMonth()->day(15)
            );

            // pela forma atual do codigo, vamos ter que calcular quantos dias uteis teve no mes anterior
            // e verificar quantos desses dias o funcionario trabalhou.
            $diasUteisMesPassado = calcularDiasUteisComSabado(
                Carbon::now()->subMonth()->day(16),
                Carbon::now()->day(15)
            );

            $this->info("Pegando funcionarios");
            $funcionarios = $solides->getFuncionariosAtivos();

            // vamos inativar todos os funcionarios.
            Employee::where('active', true)->update(['active' => false]);

            $companies = Company::all()
            ->keyBy('cod')
            ->map(fn($item) => [
                'cnpj' => $item->cnpj,
                'id' => $item->id,
            ])
            ->toArray();

            $this->info("Cadastrando funcionarios");
            $this->info("Numero de dados a serem sincronizados: ".sizeof($funcionarios));
            $inativados = 0;
            foreach ($funcionarios as $f) {
                // ignorar os que foram demitidos.
                if($f['fired'] == true){
                    $inativados++;
                    continue;
                }

                // Cadastra ou atualiza funcionário
                $birthday = isset($f['birthDate']) ? Carbon::createFromTimestampMs($f['birthDate']) : null;
                $employee = Employee::updateOrCreate(
                    ['cpf' => preg_replace('/\D/', '', $f['cpf'])],
                    [
                        'active' => true,
                        'full_name' => $f['name'],
                        'email' => $f['email'] ?? null,
                        'rg' => $f['rg'] ?? null,
                        'birthday' => $birthday ? $birthday->toDateTimeString() : null,
                        'cod_solides' => $f['id'],
                        'address' => null,
                        'company_id' => $companies[$f['company']['id']]['id'],
                        // 'user_id' => 1,
                    ]
                );

                $ferias = 0;
                $diasTrabalhadosMesPassado = $diasUteisMesPassado;
                $diasTrabalhados = $diasUteis;

                // verificamos pois pode acontecer de um funcionario ter sido contratado apos a data de verificação.
                if($f['admissionDate'] < $fim->valueOf()){
                    $diasUteisMesPassadoCalc = $diasUteisMesPassado;

                    // se ele tiver sido cadastrado entre as datas, vamos pegar a quantidade de dias trabalhados esperado para ele para fazermos o calculo.
                    if($f['admissionDate'] > $inicio->valueOf()){
                        $admissionDate = Carbon::createFromTimestampMs($f['admissionDate']);
                        $diasUteisMesPassadoCalc = calcularDiasUteisComSabado(
                            $admissionDate,
                            Carbon::now()->day(15)
                        );
                    }

                    $array_dias_trabalhados = $solides->getDiasTrabalhados($f['id'], $inicio->valueOf(), $fim->valueOf());

                    if (empty($array_dias_trabalhados)) {
                        // Nenhum ponto → inativa
                        $inativados++;
                        $this->info("Funcionario inativado por nao ter trabalhado no ultimo mes, funcionario: ".$employee->full_name."| ".$f['id']." | data de admissao: ".Carbon::createFromTimestampMs($f['admissionDate']));
                        $employee->update(['active' => false]);
                        continue;
                    }

                    $isInactive = false;
                    $lastDate = null;
                    $diasTrabalhadosMesPassado = 0;

                    foreach($array_dias_trabalhados as $index => $data){
                        $currentDate = Carbon::createFromTimestampMs($data['date']);
                        $diferenca = $currentDate->diffInDays($lastDate);

                        if($diferenca == 0)
                            continue;

                        $diasTrabalhadosMesPassado++;

                        if ($lastDate && $diferenca > 7) {
                            // gap maior que 7 dias → inativa
                            $isInactive = true;
                            break;
                        }

                        $lastDate = $currentDate;
                    }

                    $DiferençaDeDiasMesPassado = $diasUteisMesPassadoCalc - $diasTrabalhadosMesPassado;
                    $diasTrabalhados = $diasUteis - $DiferençaDeDiasMesPassado;

                    // lembrar de perguntar ao pedro
                    if($diasTrabalhados > $diasUteis) // caso aconteça de dias trabalhados serem maior que dias uteis, entao a gente seta igual dias uteis.
                        $diasTrabalhados = $diasUteis;

                    if ($isInactive || $diasTrabalhados < ($diasUteis/2)) {
                        $employee->update(['active' => false]);
                        $inativados++;
                        $this->info("Funcionario inativado por ter faltado mais de 7 dias direto, funcionario: ".$employee->full_name." | ".$f['id']." | data de admissao: ".Carbon::createFromTimestampMs($f['admissionDate']));
                        Log::info("🚫 {$employee->full_name} inativado (sem registro > 7 dias)");
                        continue;
                    } else {
                        $employee->update(['active' => true]);
                    }
                }

                // $diasTrabalhados = $diasUteis - ($faltas + $ferias);

                $EmployeesBenefits = EmployeesBenefits::where('employee_id', $employee->id)->get();
                $EmployeesBenefitsMonthly = [];

                // calcular valor total VR
                foreach($EmployeesBenefits as $empb){
                    $valueBenefit = $diasTrabalhados * $empb['qtd'] * $empb['value'];
                    $EmployeesBenefitsMonthly[] = [
                        'employee_benefit_id' => $empb['id'],
                        'value' => $empb['value'],
                        'qtd' => $empb['qtd'],
                        'work_days' => $diasTrabalhados,
                        'total_value' => $valueBenefit,
                        'paid' => true,
                        'date' => Carbon::now()->day(1)->format('Y-m-d'),
                    ];
                }

                // Inserindo na tabela de beneficios para termos um controle melhor dos dados que foram usados.
                EmployeesBenefitsMonthly::upsert(
                    $EmployeesBenefitsMonthly,  // array de registros
                    ['employee_benefit_id', 'date'], // campos únicos que determinam se atualiza ou cria
                    ['value', 'qtd', 'work_days', 'total_value', 'paid'] // campos que serão atualizados caso exista
                );

                // Dias úteis registrados esse mes
                Workday::updateOrCreate([
                    'employee_id' => $employee->id,
                    'date' => Carbon::now()->day(1)->format('Y-m-d'),
                ], [
                    'business_days' => $diasUteis,
                    'calc_days' => $diasTrabalhados
                ]);

                // Dias úteis registrados mes passado
                Workday::updateOrCreate([
                    'employee_id' => $employee->id,
                    'date' => Carbon::now()->subMonth()->day(1)->format('Y-m-d'),
                ], [
                    'business_days' => $diasUteisMesPassado,
                    'calc_days' => $diasTrabalhadosMesPassado,
                ]);
            }

            DB::commit();
            $this->info("Dados sincronizados");
            $this->info('Numero de funcionarios inativados/ignorados: '.$inativados);

            // return response()->json(['Success' => 'Excel gerado!'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->info("erro ao sincronizar dados");
            // return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
