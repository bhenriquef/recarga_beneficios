<?php

namespace App\Imports;

use App\Models\Benefit;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BenefitsSheetImport implements ToCollection, WithHeadingRow
{
    protected $user;

    public function __construct(public string $competenceMonth, $user = null)
    {
        $this->user = $user;
    }

    public function collection(Collection $rows)
    {
        $user_id = Auth::user()->id;
        foreach ($rows as $index => $row) {
            if($index < 1) // começa os dados na linha 4
                continue;

            try {
                Benefit::updateOrCreate(
                    [
                        'cod' => $row['produtos_disponiveis'],
                    ],
                    [

                        'description' => $row[1],
                        'region' => $row[2],
                        'operator' => $row[3],
                        'value' => floatval(str_replace(',', '.', ($row[4] ?? 0))),
                        'variable' => $row[5] == 'Não' ? 0 : 1,
                        'type' => $row[6],
                        'rg' => $row[7] == 'Não' ? 0 : 1,
                        'birthday' => $row[8] == 'Não' ? 0 : 1,
                        'mother_name' => $row[9] == 'Não' ? 0 : 1,
                        'address' => $row[10] == 'Não' ? 0 : 1,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Erro ao importar benefícios: ' . $e->getMessage(), ['row' => $row->toArray()]);
            }
        }
    }
}
