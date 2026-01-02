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

class DadosReaproveitamentoImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(public string $competenceMonth) {} // "Y-m"

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Observação:
            // Com WithHeadingRow, o Maatwebsite geralmente "slugifica" as chaves.
            // Ex: "VLR. SOLICITADO" -> "vlr_solicitado", "TEVE ECONOMIA?" -> "teve_economia"
            // Se no seu projeto o formatter estiver diferente, me avisa que eu ajusto.

            $mapped = [
                'cnpj'             => $this->onlyDigits($row['cnpj'] ?? null),
                'empresa'          => $this->asString($row['empresa'] ?? null),
                'pedido'           => $this->asString($row['pedido'] ?? null),
                'departamento'     => $this->asString($row['departamento'] ?? null),
                'cargo'            => $this->asString($row['cargo'] ?? null),
                'matricula'        => $this->asString($row['matricula'] ?? null),
                'cpf'              => $this->onlyDigits($row['cpf'] ?? null),
                'nome'             => $this->asString($row['nome'] ?? null),
                'id_beneficio'     => $this->asString($row['id_beneficio'] ?? null),
                'beneficio'        => $this->asString($row['beneficio'] ?? null),

                'teve_economia'    => $this->asBoolSimNao($row['teve_economia'] ?? null),

                'vlr_solicitado'   => $this->asMoney($row['vlr_solicitado'] ?? null),
                'sld_acumulado'    => $this->asMoney($row['sld_acumulado'] ?? null),
                'vlr_economia'     => $this->asMoney($row['vlr_economia'] ?? null),
                'vlr_final_pedido' => $this->asMoney($row['vlr_final_pedido'] ?? null),
            ];

            // Se quiser ignorar linhas completamente vazias (por segurança)
            if ($this->isAllNullOrEmpty($mapped)) {
                continue;
            }

            $company = Company::where('cnpj', $row['cnpj'] ?? null)->first();
            $Benefit = Benefit::where('cod', $mapped['id_beneficio'])->first();
            $Employee = Employee::where('cpf', $mapped['cpf'])->first();

            if($this->competenceMonth){
                $base = Carbon::createFromFormat('Y-m', $this->competenceMonth)->startOfMonth()->startOfDay();
            }

            if($Employee && $Benefit){
                $EmployeBenefit = EmployeesBenefits::where('employee_id', $Employee->id)->where('benefits_id', $Benefit->id)->first();

                if($EmployeBenefit){
                    EmployeesBenefitsMonthly::updateOrCreate(
                        [
                            'employee_benefit_id' => $EmployeBenefit->id,
                            'date' => $base->copy()->format('Y-m-d'),
                        ],
                        [
                            'total_value' => $mapped['vlr_solicitado'],
                            'accumulated_value' => $mapped['sld_acumulado'],
                            'saved_value' => $mapped['vlr_economia'],
                            'final_value' => $mapped['vlr_final_pedido'],
                            'qtd' => 1,
                        ]
                    );
                }
            }
        }
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
