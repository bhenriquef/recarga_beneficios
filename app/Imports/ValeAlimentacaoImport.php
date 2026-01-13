<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ValeAlimentacaoImport implements WithMultipleSheets
{
    private ValeAlimentacaoBaseImport $baseVa;

    public function __construct(public string $competenceMonth)
    {
        $this->baseVa = new ValeAlimentacaoBaseImport($this->competenceMonth);
    }

    public function sheets(): array
    {
        // Importa SOMENTE a aba "BASE VA"
        return [
            'BASE VA' => $this->baseVa,
        ];
    }

    // Mantém compatibilidade com seu Controller
    public function getNotFound(): array
    {
        return $this->baseVa->getNotFound();
    }
}
