<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlanilhaGeralVTImport implements WithMultipleSheets
{
    public SaldoMobilidadeIfoodImport $SaldoMobilidadeIfoodImport;
    public SaldoLivreIfoodImport $SaldoLivreIfoodImport;
    public AlterarParaVTImport $AlterarParaVTImport;

    public function __construct(public string $competenceMonth)
    {
        $this->SaldoMobilidadeIfoodImport = new SaldoMobilidadeIfoodImport($this->competenceMonth);
        $this->SaldoLivreIfoodImport      = new SaldoLivreIfoodImport($this->competenceMonth);
        $this->AlterarParaVTImport        = new AlterarParaVTImport($this->competenceMonth);
    }

    public function sheets(): array
    {
        return [
            'Mobilidade'       => $this->SaldoMobilidadeIfoodImport,
            'VT - Sem Cartão'  => $this->SaldoLivreIfoodImport,
            'Alterar para VT'  => $this->AlterarParaVTImport,
        ];
    }

    public function getNotFound(): array
    {
        $out = [];

        $this->appendNotFound($out, 'Mobilidade', $this->SaldoMobilidadeIfoodImport->getNotFound());
        $this->appendNotFound($out, 'VT - Sem Cartão', $this->SaldoLivreIfoodImport->getNotFound());
        $this->appendNotFound($out, 'Alterar para VT', $this->AlterarParaVTImport->getNotFound());

        return $out;
    }

    private function appendNotFound(array &$out, string $sheet, array $items): void
    {
        foreach ($items as $item) {
            // garante formato
            if (is_array($item) && isset($item['text'])) {
                $out[] = [
                    'sheet' => $sheet,
                    'text'  => $item['text'],
                ];
                continue;
            }

            // fallback se vier string
            if (is_string($item) && trim($item) !== '') {
                $out[] = [
                    'sheet' => $sheet,
                    'text'  => $item,
                ];
            }
        }
    }
}
