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
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ValeAlimentacaoImport implements ToCollection, WithHeadingRow, WithStartRow, SkipsEmptyRows
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
        $benefit = Benefit::where('cod', 'VA')->first(); // ✅ padrão do sistema
        if (!$benefit) {
            // Se o seu COD for outro (ex: ALIMENTACAO), troca aqui.
            return;
        }

        if($this->competenceMonth){
            $base = Carbon::createFromFormat('Y-m', $this->competenceMonth)->startOfMonth()->startOfDay();
        }

        // Agrupar por CPF somando Compra VA
        $grouped = []; // [cpf => ['cpf'=>..., 'nome'=>..., 'valor'=>sum]]

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
                    'ausencias' => $row['total_de_ausencias'],
                    'dias' => $row['dias/mes'],
                ];
            }

            $grouped[$cpf]['valor'] += $valor;
        }

        foreach ($grouped as $item) {
            $employee = Employee::where('cpf', $item['cpf'])->first();
            if (!$employee){
                $this->notFound[] = [
                    'text' => 'Funcionario ('.$item['cpf'].') '.$item['nome'].' não cadastrado na nossa base.'
                ];
                continue;
            }

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
