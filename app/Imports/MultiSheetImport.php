<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetImport implements WithMultipleSheets
{
    protected $user;

    public function __construct(public string $competenceMonth, $user = null)
    {
        $this->user = $user;
    }

    public function sheets(): array
    {
        return [
            'EMPRESA' => new CompaniesSheetImport($this->competenceMonth, $this->user),   // aba 1: empresas
            'PRODUTOS' => new BenefitsSheetImport($this->competenceMonth, $this->user),    // aba 3: benefícios
            'USUARIOS' => new EmployeesSheetImport($this->competenceMonth, $this->user),   // aba 2: funcionários
        ];
    }
}
