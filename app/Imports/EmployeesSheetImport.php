<?php

namespace App\Imports;

use App\Models\Benefit;
use App\Models\Employee;
use App\Models\Company;
use App\Models\EmployeesBenefits;
use App\Models\EmployeesBenefitsMonthly;
use App\Models\Workday as ModelsWorkday;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class EmployeesSheetImport implements ToCollection, WithHeadingRow
{
    protected $user;

    public function __construct($user = null)
    {
        $this->user = $user;
    }

    public function collection(Collection $rows)
    {
        $user_id = Auth::user()->id;
        $today = Carbon::today();
        $base  = $today->day >= 16 ? $today->addMonth()->copy() : $today->copy();
        $inicio = $base->copy()->startOfMonth()->format('Y-m-d');

        foreach ($rows as $index => $row) {
            if($index < 3) // começa os dados na linha 4
                continue;

            try {
                $company = Company::where('cod', $row[6] ?? null)->first();
                $date = Carbon::createFromFormat('d/m/Y', $row[14])->format('Y-m-d');

                if($company){
                    $employee = Employee::updateOrCreate(
                        ['cpf' => preg_replace('/\D/', '', $row[10]) ?? null],
                        [
                            'cod_vr' => $row[1],
                            // 'active' => $row[5] == 'ATIVO' ? 1 : 0, // sempre esta vindo com inativo (nao sei pq)
                            'full_name' => $row[2],
                            'email' => $row[3] ?? null,
                            'rg' => $row[11],
                            'birthday' => $date,
                            'mother_name' => $row[15],
                            'position' => $row[7],
                            'department' => $row[8],
                            'address' => $row[6],
                            'company_id' => $company->id,
                            'user_id' => $user_id,
                        ]
                    );

                    $workDay = ModelsWorkday::where('employee_id', $employee->id)->where('date', $inicio)->first();
                    for($i = 23; $i <= 60; $i+=4){
                        $benefit = Benefit::where('cod', $row[$i])->first();

                        if($benefit){
                            $value = floatval(str_replace(',', '.', ($row[$i+3] ?? 0)));
                            $EmployeesBenefits = EmployeesBenefits::updateOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'benefits_id' => $benefit->id,
                                ],
                                [
                                    'value' => $value,
                                    'qtd' => $row[$i+1],
                                    'days' => $row[$i+2] != '' ? $row[$i+2] : 0,
                                ]
                            );

                            if($workDay){
                                EmployeesBenefitsMonthly::updateOrCreate(
                                    [
                                        'employee_benefit_id' => $EmployeesBenefits->id,
                                        'date' => $workDay->date,
                                    ],
                                    [
                                        'value' => $value,
                                        'qtd' => $row[$i+1],
                                        'work_days' => $workDay->calc_days,
                                        'total_value' => $workDay->calc_days * $row[$i+1] * $value,
                                        'paid' => true,
                                    ]
                                );
                            }
                        }
                    }
                }


            } catch (\Throwable $e) {
                Log::error('Erro ao importar funcionário: ' . $e->getMessage(), ['row' => $row->toArray()]);
            }
        }
    }
}
