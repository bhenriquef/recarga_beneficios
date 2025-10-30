<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Workday;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\WorkDay as DateTimeExcelWorkDay;

class DashboardController extends Controller
{
    public function index()
    {

        // 1) Totais de funcionários
        $totalFuncionarios = Employee::count();
        $totalInativos     = Employee::where('active', false)->count();

        // Mês de referência: a tua sincronização grava dia 1
        $refDate = Carbon::now()->startOfMonth()->format('Y-m-d');

        // 2) Funcionários com dias trabalhados ≠ dias úteis
        $funcsDiasDiferentes = Workday::whereDate('date', $refDate)
            ->join('employees', 'employees.id', '=', 'workdays.employee_id')
            ->whereColumn('business_days', '!=', 'calc_days')
            ->where('employees.active', true)
            ->count();

        // 3) Total de benefícios (todos)
        $totalBeneficios = DB::table('employees_benefits_monthly as m')
            ->whereDate('m.date', $refDate)
            ->sum('m.total_value');

        // 4) Total iFood (filtrando por operator/cod/description)
        $totalIfood = Workday::join('employees as e', 'e.id', '=', 'workdays.employee_id')
                    ->where('e.active', true)
                    ->where('date', $refDate)
                    ->sum('calc_days');
        $totalIfood = (int)$totalIfood * 10;

        // 5) Média de benefício por funcionário (soma por funcionário, depois média)
        $avgBeneficioPorFuncionario = DB::query()
            ->fromSub(function ($sub) use ($refDate) {
                $sub->from('employees_benefits_monthly as m')
                    ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
                    ->join('employees as e', 'eb.employee_id', '=', 'e.id')
                    ->whereDate('m.date', $refDate)
                    ->where('e.active', true)
                    ->selectRaw('eb.employee_id, SUM(m.total_value) as total_por_func')
                    ->groupBy('eb.employee_id');
            }, 't')
            ->avg('total_por_func');

        // 6) Média de iFood por funcionário (mesma ideia, filtrando iFood)
        $avgIfoodPorFuncionario = $totalIfood / ($totalFuncionarios - $totalInativos);

        // 7) Média do número de passagens por funcionário (VR)
        //    Aqui considero "qtd" como passagens/dia configuradas; se quiser a média mensal, troque para AVG(m.work_days * m.qtd)
        $avgPassagensPorFuncionario = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'eb.employee_id', '=', 'e.id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->whereDate('m.date', $refDate)
            ->where('e.active', true)
            ->avg('m.qtd');

        // Top 10 benefícios mais utilizados no mês
        $topBeneficios = DB::table('employees_benefits_monthly as m')
        ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
        ->join('employees as e', 'eb.employee_id', '=', 'e.id')
        ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
        ->whereDate('m.date', $refDate)
        ->where('e.active', true)
        ->select('b.description', DB::raw('SUM(m.total_value) as total'))
        ->groupBy('b.description')
        ->orderByDesc('total')
        ->limit(10)
        ->get();

        // (Opcional) Arredonda melhor para exibição
        $fmt = fn ($v) => is_null($v) ? 0 : (float) $v;

        $refMes = Carbon::now()->subMonth()->day(16)->format('d/m')." até ".Carbon::now()->day(15)->format('d/m');

        return view('dashboard', [
            'refMes'                     => $refMes,
            'funcsDiasDiferentes'        => $funcsDiasDiferentes,
            'totalBeneficios'            => $fmt($totalBeneficios),
            'totalIfood'                 => $fmt($totalIfood),
            'avgBeneficioPorFuncionario' => $fmt($avgBeneficioPorFuncionario),
            'avgIfoodPorFuncionario'     => $fmt($avgIfoodPorFuncionario),
            'avgPassagensPorFuncionario' => $fmt($avgPassagensPorFuncionario),
            'totalFuncionarios'          => $totalFuncionarios,
            'totalInativos'              => $totalInativos,
            'topBeneficios'              => $topBeneficios,
        ]);
    }
}
