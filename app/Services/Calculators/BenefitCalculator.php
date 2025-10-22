<?php

namespace App\Services\Calculators;
use Illuminate\Support\Carbon;

class BenefitCalculator
{
    public function calculateMonthlyRecharge(Employee $employee, $month, $year)
    {
        $workingDays = $this->getWorkingDays($month, $year);
        $start = Carbon::createFromDate($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $absences = $employee->absences->whereBetween('date', [$start, $end])->count();
        $vacationDays = $employee->vacations->whereBetween('date', [$start, $end])->count();

        if ($employee->isTerminated() || $employee->hasUpcomingTermination()) {
            return 0;
        }

        if ($employee->hasFrequentAbsences(7)) {
            return 0;
        }

        $daysToRecharge = $workingDays - $absences - $vacationDays;

        return $employee->daily_transport_cost * $daysToRecharge;
    }

    private function getWorkingDays($month, $year)
    {
        $start = Carbon::createFromDate($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        return $start->diffInDaysFiltered(function ($date) {
            return in_array($date->dayOfWeek, [Carbon::MONDAY, Carbon::TUESDAY, Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY]);
        }, $end);
    }
}

