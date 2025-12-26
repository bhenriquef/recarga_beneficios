<?php

namespace App\Imports;

use App\Models\Benefit;
use App\Models\Employee;
use App\Models\EmployeesBenefits;
use App\Models\EmployeesBenefitsMonthly;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Carbon;

class SaldoLivreIfoodImport implements ToCollection, SkipsEmptyRows
{
    public function collection(Collection $rows)
    {
        $rows = $rows->slice(1)->values(); // remove header

        $grouped = []; // [matricula_norm => ['data' => ..., 'recarga_total' => 0.0]]

        foreach ($rows as $row) {
            $matricula = $this->asString($row[1] ?? null);
            $matriculaKey = $this->normalizeKey($matricula);

            if (!$matriculaKey) {
                continue;
            }

            $recargaTotal = $this->asMoney($row[66] ?? null) ?? 0.0;

            if (!isset($grouped[$matriculaKey])) {
                $grouped[$matriculaKey] = [
                    'cnpj'             => $this->onlyDigits($row[0] ?? null),
                    'matricula'        => $matricula,
                    'nome_completo'    => $this->asString($row[2] ?? null),
                    'situacao'         => $this->asString($row[5] ?? null),
                    'loja'             => $this->asString($row[6] ?? null),
                    'departamento'     => $this->asString($row[10] ?? null),
                    'cpf'              => $this->onlyDigits($row[12] ?? null),

                    // soma principal
                    'recarga_total'    => 0.0,
                ];
            }

            $grouped[$matriculaKey]['recarga_total'] += $recargaTotal;

            // opcional: preencher campos se vierem vazios e aparecerem depois
            $grouped[$matriculaKey]['nome_completo'] = $grouped[$matriculaKey]['nome_completo'] ?? $this->asString($row[2] ?? null);
            $grouped[$matriculaKey]['cpf']           = $grouped[$matriculaKey]['cpf']           ?? $this->onlyDigits($row[12] ?? null);
        }

        $Benefit = Benefit::where('cod', 'IFOOD')->first();

        $base = Carbon::today()->day(1);

        $diasUteis = calcularDiasUteisComSabado(
            $base->copy()->addMonth()->startOfMonth(),
            $base->copy()->addMonth()->endOfMonth()
        );

        foreach ($grouped as $item) {
            $Employee = Employee::where('cpf', $item['cpf'])->first();

            if($Employee){

                $EmployeesBenefits = EmployeesBenefits::updateOrCreate(
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
                        'date' => $base->addMonth()->format('Y-m-d'),
                    ],
                    [
                        'value' => ($item['recarga_total'] / $diasUteis),
                        'qtd' => 1,
                        'work_days' => $diasUteis,
                        'total_value' => $item['recarga_total'],
                        'paid' => true,
                    ]
                );
            }
        }
    }

    private function normalizeKey(?string $value): ?string
    {
        $value = $this->asString($value);
        if ($value === null) return null;

        $value = preg_replace('/\s+/', ' ', $value);
        $value = mb_strtoupper(trim($value));

        return $value === '' ? null : $value;
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

    private function asMoney($value): ?float
    {
        if ($value === null) return null;

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = $this->asString($value);
        if ($str === null) return null;

        $str = str_replace(['R$', ' '], '', $str);
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
        $str = preg_replace('/[^0-9\.\-]/', '', $str);

        if ($str === '' || $str === '-' || $str === '.') return null;

        return (float) $str;
    }
}
