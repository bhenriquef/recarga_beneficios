<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmployeeCpfLookupRequest;
use App\Models\BalanceManagement;
use App\Models\Employee;
use App\Models\EmployeesBenefits;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class EmployeeBenefitsController extends Controller
{
    public function __invoke(EmployeeCpfLookupRequest $request): JsonResponse
    {
        $cpf = $request->input('cpf');
        $monthInput = $request->input('month');
        $month = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();

        $employee = Employee::with('company')
            ->where('cpf', $cpf)
            ->first();

        if (! $employee) {
            return response()->json(['message' => 'Funcionario nao encontrado.'], 404);
        }

        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $benefitLinks = EmployeesBenefits::with('benefit')
            ->where('employee_id', $employee->id)
            ->get();

        $balanceByBenefit = BalanceManagement::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->get()
            ->groupBy('benefits_id');

        $benefits = $benefitLinks->map(function (EmployeesBenefits $link) use ($balanceByBenefit) {
            $balances = $balanceByBenefit->get($link->benefits_id) ?? collect();

            return [
                'benefit_id' => $link->benefits_id,
                'description' => $link->benefit?->description,
                'operator' => $link->benefit?->operator,
                'unit_value' => (float) $link->value,
                'quantity' => (int) $link->qtd,
                'balance_management' => $balances->map(function (BalanceManagement $balance) {
                    return [
                        'requested_value' => (float) $balance->requested_value,
                        'accumulated_balance' => (float) $balance->accumulated_balance,
                        'value_economy' => (float) $balance->value_economy,
                        'final_order_value' => (float) $balance->final_order_value,
                        'date' => (string) $balance->date,
                        'work_days' => (int) $balance->work_days,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'requested_month' => $month->format('Y-m'),
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'cpf' => $employee->cpf,
                'email' => $employee->email,
                'active' => (bool) $employee->active,
                'company' => $employee->company ? [
                    'id' => $employee->company->id,
                    'name' => $employee->company->name,
                    'cnpj' => $employee->company->cnpj,
                ] : null,
            ],
            'benefits' => $benefits,
        ]);
    }
}
