<?php

namespace App\Services\Reports;

use App\Models\Employee;
use App\Services\Calculators\BenefitCalculator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;

class BenefitRechargeReportService
{
    public function generate($employees, $month, $year)
    {
        $data = [];

        foreach ($employees as $employee) {
            $value = app(BenefitCalculator::class)
                ->calculateMonthlyRecharge($employee, $month, $year);

            if ($value > 0) {
                $data[] = [
                    'Nome' => $employee->name,
                    'Matrícula' => $employee->id,
                    'Mês' => $month,
                    'Ano' => $year,
                    'Valor VT/VR' => number_format($value, 2, ',', '.')
                ];
            }
        }

        // return Excel::download(new GenericExport($data), "recarga_vt_vr_{$month}_{$year}.xlsx");
    }
}
