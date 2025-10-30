<?php

namespace App\Imports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CompaniesSheetImport implements ToCollection, WithHeadingRow
{
    protected $user;

    public function __construct($user = null)
    {
        $this->user = $user;
    }

    public function collection(Collection $rows)
    {
        $user_id = Auth::user()->id;

        foreach ($rows as $index => $row) {
            if($index < 3) // começa os dados na linha 4
                continue;

            try {
                Company::updateOrCreate(
                    ['cod' => $row[2] ?? null, 'from' => 'VR',],
                    [
                        'name' => $row[10] ?? null,
                        'company' => $row[5],
                        'cnpj' => $row[1],
                        'street' => $row[3],
                        'number' => $row[4],
                        'complement' => $row[5],
                        'cep' => $row[6],
                        'neighborhood' => $row[7],
                        'city' => $row[8],
                        'state' => $row[9],
                        'user_id' => $user_id,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Erro ao importar empresa: ' . $e->getMessage(), ['row' => $row->toArray()]);
            }
        }
    }
}
