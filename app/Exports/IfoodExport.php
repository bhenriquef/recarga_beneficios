<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Gera a planilha EXATAMENTE igual ao modelo do iFood.
 *
 * Espera receber uma coleção/array de linhas com as chaves:
 *  [
 *    'cnpj', 'nome', 'cpf', 'data_nascimento', 'email', 'celular',
 *    'centro_custo', 'convencao_coletiva', 'grupo_entrega', 'matricula',
 *    'filtro_relatorio_recarga',
 *    'refeicao', 'alimentacao', 'mobilidade', 'livre'
 *  ]
 *
 * Obs:
 *  - cnpj/cpf/celular/matricula/etc serão exportados como TEXTO
 *  - data_nascimento pode vir como Carbon|string (dd/mm/yyyy) -> exporta dd/mm/yyyy
 *  - valores refeicao/alimentacao/mobilidade/livre podem ser numéricos ou null
 */
class IfoodExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithTitle
{
    /** @var \Illuminate\Support\Collection */
    protected Collection $rows;

    public function __construct($rows)
    {
        $this->rows = collect($rows);
    }

    public function title(): string
    {
        // Nome da aba idêntico ao arquivo modelo
        return 'Planilha Ifood';
    }

    public function headings(): array
    {
        // Cabeçalhos idênticos (com \n)
        return [
            "CNPJ\n(obrigatório)",
            "Nome\n(obrigatório)",
            "CPF\n(obrigatório)",
            "Data de nascimento\n(obrigatório)",
            "Email\n(opcional)",
            "Celular\n(opcional)",
            "Centro de custo\n(opcional)",
            "Convenção Coletiva\n(opcional)",
            "Grupo de entrega\n(opcional)",
            "Matricula\n(opcional)",
            "Filtro para relatorio de recarga\n(opcional)",
            "Refeição (Aderente ao PAT)\n(opcional)",
            "Alimentação (Aderente ao PAT)\n(opcional)",
            "Mobilidade\n(opcional)",
            "Livre\n(opcional)",
        ];
    }

    public function collection()
    {
        return $this->rows;
    }

    public function map($row): array
    {
        // normalizadores
        $toText = function ($v) {
            if (is_null($v)) return null;
            $s = (string) $v;
            // força texto simples (sem espaços invisíveis), mantendo zeros à esquerda
            return $s;
        };

        $formatDate = function ($v) {
            if (empty($v)) return null;
            if ($v instanceof Carbon) {
                return $v->format('d/m/Y');
            }
            // tenta parsear strings conhecidas
            try {
                return Carbon::parse($v)->format('d/m/Y');
            } catch (\Throwable $e) {
                // se já vier no formato certo, devolve como está
                return $v;
            }
        };

        return [
            $toText($row['cnpj'] ? preg_replace('/\D/', '', $row['cnpj']) : null),
            $toText($row['nome'] ?? null),
            $toText($row['cpf'] ?? null),
            $formatDate($row['data_nascimento'] ?? null),
            $row['email'] ?? null,
            $toText($row['celular'] ?? null),
            $row['centro_custo'] ?? null,
            $row['convencao_coletiva'] ?? null,
            $row['grupo_entrega'] ?? null,
            $toText($row['matricula'] ?? null),
            $row['filtro_relatorio_recarga'] ?? null,
            // valores dos produtos (numéricos ou null)
            $row['refeicao'] ?? null,
            $row['alimentacao'] ?? null,
            $row['mobilidade'] ?? null,
            $row['livre'] ?? null,
        ];
    }

    public function columnFormats(): array
    {
        return [
            // A-K como TEXTO (evita perder zeros à esquerda e formatação estranha)
            'A' => NumberFormat::FORMAT_TEXT, // CNPJ
            'B' => NumberFormat::FORMAT_TEXT, // Nome
            'C' => NumberFormat::FORMAT_TEXT, // CPF
            'D' => NumberFormat::FORMAT_TEXT, // Data (mantida como texto dd/mm/yyyy para ficar idêntico)
            'E' => NumberFormat::FORMAT_TEXT, // Email
            'F' => NumberFormat::FORMAT_TEXT, // Celular
            'G' => NumberFormat::FORMAT_TEXT, // Centro de custo
            'H' => NumberFormat::FORMAT_TEXT, // Convenção Coletiva
            'I' => NumberFormat::FORMAT_TEXT, // Grupo de entrega
            'J' => NumberFormat::FORMAT_TEXT, // Matricula
            'K' => NumberFormat::FORMAT_TEXT, // Filtro para relatorio de recarga

            // L-O números (use NUMBER para quantidades/valores simples; ajuste se precisar moeda)
            'L' => NumberFormat::FORMAT_NUMBER, // Refeição
            'M' => NumberFormat::FORMAT_NUMBER, // Alimentação
            'N' => NumberFormat::FORMAT_NUMBER, // Mobilidade
            'O' => NumberFormat::FORMAT_NUMBER, // Livre
        ];
    }
}
