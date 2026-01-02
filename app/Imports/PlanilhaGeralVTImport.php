<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlanilhaGeralVTImport implements WithMultipleSheets
{
    public function __construct(public string $competenceMonth) {} // "Y-m"

    public function sheets(): array
    {
        return [
            'Mobilidade'       => new SaldoMobilidadeIfoodImport($this->competenceMonth),
            'VT - Sem Cartão'  => new SaldoLivreIfoodImport($this->competenceMonth),
            'Alterar para VT'  => new AlterarParaVTImport($this->competenceMonth),
        ];
    }
}
