<?php
namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $companies = \App\Models\Company::query()
            ->withCount('employees')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            })
            ->orderByDesc('employees_count')
            ->orderBy('name')
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('companies.index', compact('companies', 'search'));
    }

    public function show($id)
    {
        $company = Company::findOrFail($id);

        // Total de funcionários ativos
        $totalFuncionarios = Employee::where('company_id', $id)
            ->where('active', true)
            ->count();

        $funcionariosInativos = Employee::where('company_id', $id)
            ->where('active', false)
            ->count();

        // Média de presença geral
        $mediaPresencaGeral = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->selectRaw('ROUND(AVG(w.calc_days / w.business_days * 100), 1) as media')
            ->groupBy('e.company_id')
            ->value('media');

        // Média de faltas por funcionário
        $mediaFaltas = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->selectRaw('ROUND(AVG(w.business_days - w.calc_days), 1) as media')
            ->groupBy('e.company_id')
            ->value('media');

        // Benefícios — total e média
        $beneficios = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->where('e.company_id', $id)
            ->selectRaw('
                e.company_id,
                SUM(m.total_value) as total,
                ROUND(AVG(m.total_value), 2) as media
            ')
            ->groupBy('e.company_id')
            ->first();

        // iFood — total e média
        $ifood = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->selectRaw('
                e.company_id,
                SUM(w.calc_days * 10) as total,
                ROUND(AVG(w.calc_days * 10), 2) as media
            ')
            ->groupBy('e.company_id')
            ->first();

        // Histórico mensal consolidado
        $historico = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->selectRaw("
                DATE_FORMAT(w.date, '%m/%Y') as mes,
                ROUND(AVG(w.calc_days), 1) as media_dias_trabalhados,
                ROUND(AVG(w.business_days), 1) as media_dias_uteis,
                SUM(w.calc_days * 10) as total_ifood,
                (
                    SELECT COALESCE(SUM(m2.total_value), 0)
                    FROM employees_benefits_monthly m2
                    JOIN employees_benefits eb2 ON eb2.id = m2.employee_benefit_id
                    JOIN employees e2 ON e2.id = eb2.employee_id
                    WHERE e2.company_id = e.company_id
                    AND DATE_FORMAT(m2.date, '%m/%Y') = DATE_FORMAT(w.date, '%m/%Y')
                ) as total_beneficios
            ")
            ->groupBy('mes', 'e.company_id', 'w.date')
            ->orderByRaw("w.date ASC")
            ->get();

        $topFuncionario = DB::table('workdays as w')
        ->join('employees as e', 'e.id', '=', 'w.employee_id')
        ->where('e.company_id', $id)
        ->selectRaw('e.full_name, ROUND(AVG(w.calc_days),1) as media_dias')
        ->groupBy('e.id', 'e.full_name')
        ->orderByDesc('media_dias')
        ->first();

        $historicoTabela = $historico->sortByDesc(function ($item) {
            return \Carbon\Carbon::createFromFormat('m/Y', $item->mes);
        })->values();

        $beneficiosDetalhados = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->where('e.company_id', $id)
            ->select('b.description as beneficio', DB::raw('SUM(m.total_value) as total'))
            ->groupBy('b.id', 'b.description')
            ->get();

        // Agora adiciona o total do iFood também
        $totalIfood = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->sum(DB::raw('w.calc_days * 10'));

        // Concatena o iFood como mais um item
        $beneficiosDetalhados->push((object)[
            'beneficio' => 'iFood',
            'total' => $totalIfood,
        ]);


        return view('companies.show', compact(
            'company',
            'totalFuncionarios',
            'mediaPresencaGeral',
            'mediaFaltas',
            'beneficios',
            'ifood',
            'historico',
            'historicoTabela',
            'topFuncionario',
            'funcionariosInativos',
            'beneficiosDetalhados',
        ));
    }

}
