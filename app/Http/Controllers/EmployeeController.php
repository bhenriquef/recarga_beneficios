<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\PDF;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $employees = \App\Models\Employee::query()
            ->when($search, function ($query, $search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('cod_solides', 'like', "%{$search}%")
                    ->orWhere('cod_vr', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            })
            ->orderBy('full_name')
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('employees.index', compact('employees', 'search'));
    }

    public function show($id)
    {
        $employee = \App\Models\Employee::with('company')->findOrFail($id);

        // Médias e totais por mês
        $mediaDiasTrabalhados = DB::table('workdays')
            ->where('employee_id', $id)
            ->selectRaw('AVG(business_days) as media')
            ->value('media');

        $mediaFaltas = DB::table('workdays')
            ->where('employee_id', $id)
            ->selectRaw('AVG(business_days-calc_days) as media')
            ->value('media');


        $mediaBeneficios = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->where('eb.employee_id', $id)
            ->avg('m.total_value');

        $mediaIfood = DB::table('workdays')
            ->where('employee_id', $id)
            ->selectRaw('AVG(calc_days * 10) as media_ifood')
            ->value('media_ifood');

        $totalIfood = DB::table('workdays')
            ->where('employee_id', $id)
            ->selectRaw('SUM(calc_days * 10) as total_ifood')
            ->value('total_ifood');

        $totalBeneficios = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->where('eb.employee_id', $id)
            ->sum('m.total_value');

        $beneficiosUsados = DB::table('employees_benefits as eb')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->where('eb.employee_id', $id)
            ->select('b.description', 'b.operator', 'b.value', 'eb.qtd')
            ->get();

        $historico = DB::table('workdays as w')
            ->where('w.employee_id', $id)
            ->selectRaw("
                DATE_FORMAT(w.date, '%m/%Y') as mes,
                w.employee_id,
                ROUND(AVG(w.calc_days), 1) as dias_trabalhados,
                SUM(w.business_days) as dias_uteis,
                SUM(w.calc_days * 10) as total_ifood,
                (
                    SELECT COALESCE(SUM(m.total_value), 0)
                    FROM employees_benefits_monthly m
                    JOIN employees_benefits eb ON eb.id = m.employee_benefit_id
                    WHERE eb.employee_id = w.employee_id
                    AND DATE_FORMAT(m.date, '%m/%Y') = DATE_FORMAT(w.date, '%m/%Y')
                ) as total_beneficios
            ")
            ->groupBy('w.employee_id', "w.date")
            ->orderByRaw("STR_TO_DATE(DATE_FORMAT(w.date, '%m/%Y'), '%m/%Y') DESC")
            ->get();

            $presencaMedia = DB::table('workdays')
                ->where('employee_id', $id)
                ->selectRaw('ROUND(AVG(calc_days / business_days * 100), 1) as media_presenca')
                ->value('media_presenca');

            $rankingQuery = DB::table('workdays as w')
                ->join('employees as e', 'e.id', '=', 'w.employee_id')
                ->where('e.company_id', $employee->company_id)
                ->selectRaw('w.employee_id, AVG(w.calc_days) as media_dias')
                ->groupBy('w.employee_id')
                ->orderBy('media_dias')
                ->get();

            $posicaoRanking = $rankingQuery->search(fn($item) => $item->employee_id == $id) + 1;
            $totalFuncionariosEmpresa = Employee::where('active', true)->count();

            $beneficiosAcumulados = DB::table('employees_benefits_monthly as ebm')
                ->join('employees_benefits as eb', 'eb.id', '=', 'ebm.employee_benefit_id')
                ->where('eb.employee_id', $id)
                ->selectRaw("
                    DATE_FORMAT(ebm.date, '%m/%Y') as mes,
                    SUM(ebm.total_value) as total_beneficio,
                    SUM(SUM(ebm.total_value)) OVER (ORDER BY ebm.date) as acumulado
                ")
                ->groupBy('mes', 'ebm.date')
                ->orderBy('ebm.date')
                ->get();


        return view('employees.show', compact(
            'employee',
            'mediaDiasTrabalhados',
            'mediaFaltas',
            'mediaIfood',
            'mediaBeneficios',
            'totalIfood',
            'totalBeneficios',
            'beneficiosUsados',
            'historico',
            'presencaMedia',
            'posicaoRanking',
            'totalFuncionariosEmpresa',
            'beneficiosAcumulados'
        ));
    }

}
