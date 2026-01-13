<?php

namespace App\Imports;

use App\Models\Benefit;
use App\Models\Employee;
use App\Models\EmployeesBenefits;
use App\Models\EmployeesBenefitsMonthly;
use App\Models\Workday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ValeAlimentacaoBaseImport implements ToCollection, WithHeadingRow, WithStartRow, SkipsEmptyRows, WithCalculatedFormulas
{
    public function __construct(public string $competenceMonth) {} // "Y-m"

    public function headingRow(): int { return 2; }
    public function startRow(): int { return 3; }

    private array $notFound = [];

    public function getNotFound(): array
    {
        return $this->notFound;
    }

    public function collection(Collection $rows)
    {
        if ($this->competenceMonth) {
            $base = Carbon::createFromFormat('Y-m', $this->competenceMonth)->startOfMonth()->startOfDay();
        }

        // Agrupar por CPF somando Compra VA
        $grouped = [];

        foreach ($rows as $row) {
            $cpf = $this->onlyDigits($row['cpf'] ?? null);
            if (!$cpf) continue;

            $valor = $this->asMoney($row['compra_va'] ?? null) ?? 0.0;
            if ($valor <= 0) continue;

            if (!isset($grouped[$cpf])) {
                $grouped[$cpf] = [
                    'cpf'  => $cpf,
                    'nome' => $this->asString($row['colaborador'] ?? null),
                    'valor'=> 0.0,
                    'ausencias' => isset($row['total_de_ausencias']) ? $row['total_de_ausencias'] : 0,
                    'dias' => isset($row['diasmes']) ? $row['diasmes'] : 0,
                ];
            }

            $grouped[$cpf]['valor'] += $valor;
        }

        $Benefit = Benefit::where('cod', 'VALE_ALIMENTACAO')->first();

        // deletar os dados que ja existem de ifood para nao termos problema com o calculo.
        EmployeesBenefitsMonthly::join('employees_benefits as eb', 'eb.id', '=', 'employees_benefits_monthly.employee_benefit_id')
        ->join('benefits', 'benefits.id', '=', 'eb.benefits_id')
        ->where('benefits.cod', 'VALE_ALIMENTACAO')
        ->where('employees_benefits_monthly.date', $base->copy()->format('Y-m-d'))
        ->forceDelete();

        EmployeesBenefits::join('benefits', 'benefits.id', '=', 'employees_benefits.benefits_id')
        ->where('benefits.cod', 'VALE_ALIMENTACAO')->delete();

        foreach ($grouped as $item) {
            $employee = Employee::where('cpf', $item['cpf'])->first();
            if (!$employee) {
                $employee = Employee::create([
                    'cpf' => $item['cpf'],
                    'full_name' => $item['nome'],
                    'company_id' => 1, // vamos setar padrao 1 ate resolverem o problema do excel.
                ]);
            }

            $diasTrabalhados = $item['dias'] - $item['ausencias'];
            $EmployeBenefit = EmployeesBenefits::withTrashed()->where('employee_id', $employee->id)->where('benefits_id', $Benefit->id)->first();

            if(!$EmployeBenefit){
                $EmployeBenefit = EmployeesBenefits::withTrashed()->updateOrCreate([
                    'employee_id' => $employee->id,
                    'benefits_id' => $Benefit->id,
                ],
                [
                    'qtd' => 1,
                    'value' => 10,
                ]);
            }

            EmployeesBenefitsMonthly::updateOrCreate(
                [
                    'employee_benefit_id' => $EmployeBenefit->id,
                    'date' => $base->copy()->format('Y-m-d'),
                ],
                [
                    'total_value' => $item['valor'],
                    'accumulated_value' => 0,
                    'saved_value' => 0,
                    'final_value' => $item['valor'],
                    'value' => $item['valor'] / $diasTrabalhados,
                    'qtd' => 1,
                    'work_days' => $diasTrabalhados,
                ]
            );

            Workday::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $base->copy()->format('Y-m-d'),
                ],
                [
                    'business_days' => $item['dias'],
                    'calc_days' => $item['dias'] - $item['ausencias'],
                    'worked_days' => $item['dias'] - $item['ausencias'],
                ]
            );
        }
    }

    private function asString($value): ?string
    {
        if ($value === null) return null;
        $str = trim((string)$value);
        return $str === '' ? null : $str;
    }

    private function onlyDigits($value): ?string
    {
        $str = $this->asString($value);
        if ($str === null) return null;
        $digits = preg_replace('/\D+/', '', $str);
        return $digits === '' ? null : $digits;
    }

    private function asMoney($value): ?float
    {
        if ($value === null) return null;
        if (is_int($value) || is_float($value)) return (float)$value;

        $str = $this->asString($value);
        if ($str === null) return null;

        $str = str_replace(['R$', ' '], '', $str);
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
        $str = preg_replace('/[^0-9\.\-]/', '', $str);

        return ($str === '' || $str === '-' || $str === '.') ? null : (float)$str;
    }
}
