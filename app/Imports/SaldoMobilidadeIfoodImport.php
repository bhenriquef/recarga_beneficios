<?php

namespace App\Imports;

use App\Models\Benefit;
use App\Models\Employee;
use App\Models\EmployeesBenefits;
use App\Models\EmployeesBenefitsMonthly;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

class SaldoMobilidadeIfoodImport implements ToCollection, SkipsEmptyRows
{

    public function __construct(public string $competenceMonth) {} // "Y-m"

    public function collection(Collection $rows)
    {
        $headerIndex = 0;
        if (isset($rows[0][0]) && is_string($rows[0][0]) && trim($rows[0][0]) !== 'NOME') {
            $headerIndex = 1; // quando header está na linha 2
        }
        $rows = $rows->slice($headerIndex + 1)->values();

        $grouped = []; // [nome_norm => ['data' => ..., 'sum_valor_creditado' => 0.0]]

        foreach ($rows as $row) {
            $nome = $this->asString($row[0] ?? null);
            $nomeKey = $this->normalizeKey($nome);

            // Se não tem nome, ignora (linha inválida)
            if (!$nomeKey) {
                continue;
            }

            $valorCreditado = $this->asMoney($row[7] ?? null) ?? 0.0;

            // Se quiser guardar mais campos "base" (primeira ocorrência)
            if (!isset($grouped[$nomeKey])) {
                $grouped[$nomeKey] = [
                    'nome'           => $nome,
                    'cargo'          => $this->asString($row[1] ?? null),
                    'empresa_1'       => $this->asString($row[2] ?? null),
                    'departamento'    => $this->asString($row[3] ?? null),
                    'empresa_2'       => $this->asString($row[4] ?? null),
                    'valor_fixo'      => $this->asMoney($row[5] ?? null), // (não somando)
                    'observacao'      => $this->asString($row[7] ?? null),
                    'valor_creditado' => 0.0, // vamos somar aqui
                ];
            }

            // soma principal
            $grouped[$nomeKey]['valor_creditado'] += $valorCreditado;

            // opcional: se cargo/departamento vierem vazios em uma linha e preenchidos em outra
            $grouped[$nomeKey]['nome']       = $grouped[$nomeKey]['nome'];
            $grouped[$nomeKey]['departamento']= $grouped[$nomeKey]['departamento']?? $this->asString($row[3] ?? null);
        }

        $Benefit = Benefit::where('cod', 'MOBILIDADE')->first();

        if($this->competenceMonth){
            $base = Carbon::createFromFormat('Y-m', $this->competenceMonth)->startOfMonth()->startOfDay();
        }

        $diasUteis = calcularDiasUteisComSabado(
            $base->copy()->startOfMonth(),
            $base->copy()->endOfMonth()
        );

        foreach ($grouped as $item) {
            $Employee = Employee::where('full_name', 'like', '%'.$item['nome'].'%')->first();

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
                        'date' => $base->copy()->format('Y-m-d'),
                    ],
                    [
                        'value' => ($item['valor_creditado'] / $diasUteis),
                        'qtd' => 1,
                        'work_days' => $diasUteis,
                        'total_value' => $item['valor_creditado'],
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

        // remove espaços duplicados e padroniza maiúsculo
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
