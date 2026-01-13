<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Benefit;
use App\Models\Employee;
use App\Models\EmployeesBenefits;
use App\Models\EmployeesBenefitsMonthly;
use Illuminate\Support\Carbon;

class AlterarParaVTImport implements ToCollection, SkipsEmptyRows
{
    public function __construct(public string $competenceMonth) {} // "Y-m"

    private array $notFound = [];

    public function getNotFound(): array
    {
        return $this->notFound;
    }

    public function collection(Collection $rows)
    {
        // header nessa aba é linha 1
        $rows = $rows->slice(1)->values();

        $grouped = []; // [cpf => ['cpf'=>..., 'nome'=>..., 'valor_creditado'=>sum...]]

        foreach ($rows as $row) {
            $cpf = $this->onlyDigits($row[1] ?? null);
            if (!$cpf) continue;

            $valor = $this->asMoney($row[7] ?? null) ?? 0.0;

            if (!isset($grouped[$cpf])) {
                $grouped[$cpf] = [
                    'nome'           => $this->asString($row[0] ?? null),
                    'cpf'            => $cpf,
                    'data_nascimento'=> $this->asString($row[2] ?? null),
                    'departamento'   => $this->asString($row[3] ?? null),
                    'ausencias'      => $this->asInt($row[4] ?? null),
                    'empresa'        => $this->asString($row[5] ?? null),
                    'valor_fixo'      => $this->asMoney($row[6] ?? null),
                    'observacao'     => $this->asString($row[8] ?? null),
                    'valor_creditado'=> 0.0,
                ];
            }

            $grouped[$cpf]['valor_creditado'] += $valor;
        }

        $Benefit = Benefit::where('cod', 'IFOOD')->first();

        if($this->competenceMonth){
            $base = Carbon::createFromFormat('Y-m', $this->competenceMonth)->startOfMonth()->startOfDay();
        }

        $diasUteis = calcularDiasUteisComSabado(
            $base->copy()->startOfMonth(),
            $base->copy()->endOfMonth()
        );

        foreach ($grouped as $item) {
            $totalDiasTrabalhados = $diasUteis - $item['ausencias'];
            $Employee = Employee::where('cpf', $item['cpf'])->first();

            if (!$Employee) {
                $Employee = Employee::create([
                    'cpf' => $item['cpf'],
                    'full_name' => $item['nome'],
                    'company_id' => 1, // vamos setar padrao 1 ate resolverem o problema do excel.
                ]);
            }

            $EmployeesBenefits = EmployeesBenefits::withTrashed()->updateOrCreate(
                [
                    'employee_id' => $Employee->id,
                    'benefits_id' => $Benefit->id,
                ],
                [
                    'value' => 0,
                    'qtd' => 1,
                    'days' => 0,
                ]
            );

            EmployeesBenefitsMonthly::updateOrCreate(
                [
                    'employee_benefit_id' => $EmployeesBenefits->id,
                    'date' => $base->copy()->format('Y-m-d'),
                ],
                [
                    'value' => ($item['valor_creditado'] / $totalDiasTrabalhados),
                    'qtd' => 1,
                    'work_days' => $totalDiasTrabalhados,
                    'total_value' => $item['valor_creditado'],
                    'paid' => true,
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

    private function asInt($value): ?int
    {
        if ($value === null) return null;
        if (is_int($value)) return $value;
        if (is_float($value)) return (int) round($value);

        $str = $this->asString($value);
        if ($str === null) return null;
        $str = preg_replace('/[^0-9\-]/', '', $str);
        return ($str === '' || $str === '-') ? null : (int)$str;
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
