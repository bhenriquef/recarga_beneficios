<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class VRExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    protected $dados;

    public function __construct($dados)
    {
        $this->dados = $dados;
    }

    public function collection()
    {
        return collect($this->dados)->map(function ($item) {
            return [
                $item['cpf'],
                $item['nome'],
                $item['codigo_beneficio'],
                $item['valor_unitario'],
                $item['qtd_passagens_dia'],
                $item['dias_uteis'],
                $item['valor_total'],
                $item['empresa'],
                $item['cnpj'],
                $item['mes_referencia'],
                $item['data_geracao'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'CPF',
            'NOME',
            'CODIGO_BENEFICIO',
            'VALOR_UNITARIO',
            'QTDE_PASSAGENS_DIA',
            'DIAS_UTEIS',
            'VALOR_TOTAL',
            'EMPRESA',
            'CNPJ',
            'MES_REFERENCIA',
            'DATA_GERACAO',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
            'G' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
        ];
    }
}
