<?php

namespace App\Imports;

use App\Models\Benefit;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeesBenefits;
use App\Models\EmployeesBenefitsMonthly;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Carbon;

class RecemAdmitidosImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(public string $competenceMonth) {} // "Y-m"

    public function headingRow(): int
    {
        return 1;
    }

    private array $notFound = [];

    public function getNotFound(): array
    {
        return $this->notFound;
    }

    public function collection(Collection $rows)
    {
        if($this->competenceMonth){
            $base = Carbon::createFromFormat('Y-m', $this->competenceMonth)->startOfMonth()->startOfDay();
        }

        $diasUteis = calcularDiasUteisComSabado(
            $base->copy()->startOfMonth(),
            $base->copy()->endOfMonth()
        );

        foreach ($rows as $row) {
            $mapped = [
                'cnpj'             => $this->onlyDigits($row['cnpj_obrigatorio'] ?? null),
                'cpf'              => $this->onlyDigits($row['cpf_obrigatorio'] ?? null),
                'nome'             => $this->asString($row['nome_obrigatorio'] ?? null),
                'alimentacao'   => $this->asMoney($row['alimentacao_aderente_ao_pat_opcional'] ?? null),
                'mobilidade'    => $this->asMoney($row['mobilidade_opcional'] ?? null),
                'livre'     => $this->asMoney($row['livre_opcional'] ?? null),
            ];

            $company = Company::where('cnpj', $row['cnpj_obrigatorio'] ?? null)->first();

            $Employee = Employee::where(function($query) use ($mapped){
                $query->where('cpf', $mapped['cpf'])->orWhere('full_name', 'like', '%'.$mapped['nome'].'%');
            })->first();

            $Alimentacao = Benefit::where('cod', 'VALE_ALIMENTACAO')->first();
            $Mobilidade = Benefit::where('cod', 'MOBILIDADE')->first();
            $Livre = Benefit::where('cod', 'IFOOD')->first();

            // EmployeesBenefits::all()->delete(); // vamos inativar todos os dados anteriores para criamos os novos.
            if (!$Employee) {
                $Employee = Employee::create([
                    'cpf' => $mapped['cpf'],
                    'full_name' => $mapped['nome'],
                    'company_id' => $company ? $company->id : 1, // vamos setar padrao 1 ate resolverem o problema do excel.
                ]);
            }

            if($mapped['alimentacao']){
                $EmployeBenefit = $this->createEmployeeBenefit($Employee->id, $Alimentacao->id, ($mapped['alimentacao'] / $diasUteis));

                EmployeesBenefitsMonthly::updateOrCreate(
                    [
                        'employee_benefit_id' => $EmployeBenefit->id,
                        'date' => $base->copy()->format('Y-m-d'),
                    ],
                    [
                        'total_value' => $mapped['alimentacao'],
                        'accumulated_value' => 0,
                        'saved_value' => 0,
                        'final_value' => $mapped['alimentacao'] ?: 0,
                        'qtd' => 1,
                        'work_days' => $diasUteis,
                    ]
                );
            }

            if($mapped['mobilidade']){
                $EmployeBenefit = $this->createEmployeeBenefit($Employee->id, $Mobilidade->id, ($mapped['mobilidade'] / $diasUteis));

                EmployeesBenefitsMonthly::updateOrCreate(
                    [
                        'employee_benefit_id' => $EmployeBenefit->id,
                        'date' => $base->copy()->format('Y-m-d'),
                    ],
                    [
                        'total_value' => $mapped['mobilidade'],
                        'accumulated_value' => 0,
                        'saved_value' => 0,
                        'final_value' => $mapped['mobilidade'] ?: 0,
                        'qtd' => 1,
                        'work_days' => $diasUteis,
                    ]
                );
            }

            if($mapped['livre']){
                $EmployeBenefit = $this->createEmployeeBenefit($Employee->id, $Livre->id, ($mapped['livre'] / $diasUteis));

                EmployeesBenefitsMonthly::updateOrCreate(
                    [
                        'employee_benefit_id' => $EmployeBenefit->id,
                        'date' => $base->copy()->format('Y-m-d'),
                    ],
                    [
                        'total_value' => $mapped['livre'],
                        'accumulated_value' => 0,
                        'saved_value' => 0,
                        'final_value' => $mapped['livre'] ?: 0,
                        'qtd' => 1,
                        'work_days' => $diasUteis,
                    ]
                );
            }
        }
    }

    private function createEmployeeBenefit($employee_id, $benefit_id, $value){
        return EmployeesBenefits::withTrashed()->updateOrCreate([
            'employee_id' => $employee_id,
            'benefits_id' => $benefit_id,
            'value' => $value,
        ],
        [
            'qtd' => 1,
        ]);
    }

    private function asString($value): ?string
    {
        if ($value === null) return null;

        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }

    private function onlyDigits($value): ?string
    {
        $str = $this->asString($value);
        if ($str === null) return null;

        $digits = preg_replace('/\D+/', '', $str);
        return $digits === '' ? null : $digits;
    }

    /**
     * Converte "SIM"/"NÃO" (ou variações) em boolean.
     */
    private function asBoolSimNao($value): ?bool
    {
        $str = $this->asString($value);
        if ($str === null) return null;

        $str = mb_strtoupper($str);

        if (in_array($str, ['SIM', 'S', 'YES', 'Y', 'TRUE', '1'], true)) return true;
        if (in_array($str, ['NÃO', 'NAO', 'N', 'NO', 'FALSE', '0'], true)) return false;

        return null; // se vier algo inesperado
    }

    /**
     * Converte moeda pt-BR:
     * "1.234,56" -> 1234.56
     * "0,00" -> 0.00
     * Aceita número também.
     */
    private function asMoney($value): ?float
    {
        if ($value === null) return null;

        // Se já vier numérico do Excel
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = $this->asString($value);
        if ($str === null) return null;

        // remove "R$", espaços etc.
        $str = str_replace(['R$', ' '], '', $str);

        // remove separador de milhar e troca vírgula por ponto
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);

        // mantém somente dígitos, ponto e sinal
        $str = preg_replace('/[^0-9\.\-]/', '', $str);

        if ($str === '' || $str === '-' || $str === '.') return null;

        return (float) $str;
    }

    private function isAllNullOrEmpty(array $data): bool
    {
        foreach ($data as $v) {
            if ($v !== null && $v !== '') return false;
        }
        return true;
    }
}
