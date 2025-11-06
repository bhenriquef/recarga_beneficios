<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Workday;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {

        // Base para seleção automática: se dia >= 16 usa o mês atual, senão mês anterior
        $today = Carbon::today();
        $base  = $today->day >= 16 ? $today->copy() : $today->copy()->subMonth();

        // Mês/ano vindos da UI (ou automáticos)
        $mes = (int) ($request->get('m') ?? $base->format('m'));
        $ano = (int) ($request->get('y') ?? $base->year);

        // Janela: 16/M selecionado até 15/(M+1)
        $inicio = Carbon::createFromDate($ano, $mes, 16)->startOfDay();
        $fim    = $inicio->copy()->addMonth()->subDay()->endOfDay();

        // Label exibida
        $refMes   = $inicio->format('d/m') . ' até ' . $fim->format('d/m');
        $mesAtual = str_pad($mes, 2, '0', STR_PAD_LEFT);

        // Lista de meses (pt-BR) para o <select>
        $meses = collect(range(1, 12))->mapWithKeys(function ($m) {
            return [str_pad($m, 2, '0', STR_PAD_LEFT)
                => Carbon::create()->month($m)->locale('pt_BR')->monthName];
        })->toArray();

        // 1) Totais de funcionários
        $totalFuncionarios = Employee::count();
        $totalInativos     = Employee::where('active', false)->count();

        // Mês de referência
        $refDate = $inicio->startOfMonth()->format('Y-m-d');

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
        $avgIfoodPorFuncionario = 0;
        if($totalFuncionarios > 0){
            $avgIfoodPorFuncionario = $totalIfood / ($totalFuncionarios - $totalInativos);
        }

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

        // Arredonda melhor para exibição
        $fmt = fn ($v) => is_null($v) ? 0 : (float) $v;

        return view('dashboard', [
            'refMes'   => $refMes,
            'mesAtual' => $mesAtual,
            'meses'    => $meses,
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
