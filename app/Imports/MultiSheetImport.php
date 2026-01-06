<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetImport implements WithMultipleSheets
{
    protected $user;

    public CompaniesSheetImport $companies;
    public BenefitsSheetImport $benefits;
    public EmployeesSheetImport $employees;

    public function __construct(public string $competenceMonth, $user = null)
    {
        $this->user = $user;

        $this->companies = new CompaniesSheetImport($this->competenceMonth, $this->user);
        $this->benefits  = new BenefitsSheetImport($this->competenceMonth, $this->user);
        $this->employees = new EmployeesSheetImport($this->competenceMonth, $this->user);
    }

    public function sheets(): array
    {
        return [
            'EMPRESA'  => $this->companies,
            'PRODUTOS' => $this->benefits,
            'USUARIOS' => $this->employees,
        ];
    }

    /**
     * Retorna lista FLAT:
     * [
     *   ['sheet' => 'EMPRESA', 'text' => '...'],
     *   ['sheet' => 'PRODUTOS', 'text' => '...'],
     * ]
     */
    public function getNotFound(): array
    {
        $out = [];

        $this->appendNotFound($out, 'EMPRESA', $this->companies->getNotFound());
        $this->appendNotFound($out, 'PRODUTOS', $this->benefits->getNotFound());
        $this->appendNotFound($out, 'USUARIOS', $this->employees->getNotFound());

        return $out;
    }

    private function appendNotFound(array &$out, string $sheet, array $items): void
    {
        foreach ($items as $item) {
            if (is_array($item) && isset($item['text'])) {
                $out[] = [
                    'sheet' => $sheet,
                    'text'  => $item['text'],
                ];
                continue;
            }

            // fallback se algum import devolver string direta
            if (is_string($item) && trim($item) !== '') {
                $out[] = [
                    'sheet' => $sheet,
                    'text'  => $item,
                ];
            }
        }
    }
}
