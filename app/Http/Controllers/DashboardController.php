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
        // Base usada para default de mês
        $today = Carbon::today();
        $base  = $today->day >= 16 ? $today->copy()->addMonth() : $today->copy();

        /**
         * ANOS DISPONÍVEIS
         * - Pegamos todos os anos que existem em workdays e employees_benefits_monthly
         * - Fazemos um UNION e ordenamos
         */
        $anosQuery = DB::table('workdays')
            ->selectRaw('YEAR(date) as ano')
            ->distinct()
            ->union(
                DB::table('employees_benefits_monthly')
                    ->selectRaw('YEAR(date) as ano')
                    ->distinct()
            );

        $anos = DB::query()
            ->fromSub($anosQuery, 'anos')
            ->select('ano')
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->toArray();

        // Se ainda não há dados em nenhuma tabela, usa o ano atual como fallback
        if (empty($anos)) {
            $anos = [$today->year];
        }

        // ANO selecionado: se veio no request e existe na lista, usa; senão pega o mais recente
        $requestedAno = $request->get('y');

        if ($requestedAno && in_array((int) $requestedAno, $anos, true)) {
            $anoSelecionado = (int) $requestedAno;
        } else {
            // tenta usar o ano "base" se ele existe na lista, senão pega o maior ano com dado
            $anoSelecionado = in_array($base->year, $anos, true)
                ? $base->year
                : max($anos);
        }

        // MÊS selecionado continua a mesma lógica de antes
        $mesSelecionado = (int) ($request->get('m') ?? $base->format('m'));

        // Janela: 16/M até 15/(M+1) do ano selecionado
        $inicio = Carbon::createFromDate($anoSelecionado, $mesSelecionado, 16)->startOfDay();
        $fim    = $inicio->copy()->addMonth()->subDay()->endOfDay();

        // Label exibida
        $refMes   = $inicio->copy()->subMonth(2)->format('d/m') . ' até ' . $fim->copy()->subMonth(2)->format('d/m');
        $mesAtual = str_pad($mesSelecionado, 2, '0', STR_PAD_LEFT);

        // Lista de meses (pt-BR) para o <select>
        $meses = collect(range(1, 12))->mapWithKeys(function ($m) {
            return [str_pad($m, 2, '0', STR_PAD_LEFT)
                => Carbon::create()->month($m)->locale('pt_BR')->monthName];
        })->toArray();

        // 1) Totais de funcionários
        $totalFuncionarios = Employee::count();
        $totalInativos     = Employee::where('active', false)->count();

        // Mês de referência para as tabelas mensais (sempre dia 01, mas já com ANO correto)
        $refDate = $inicio->copy()->startOfMonth()->format('Y-m-d');

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

        // 4) Total iFood (exemplo com calc_days * 10)
        $totalIfood = Workday::join('employees as e', 'e.id', '=', 'workdays.employee_id')
            ->where('e.active', true)
            ->whereDate('date', $refDate)
            ->sum('calc_days');
        $totalIfood = (int) $totalIfood * 10;

        // 5) Média de benefício por funcionário
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

        // 6) Média de iFood por funcionário
        $avgIfoodPorFuncionario = 0;
        if ($totalFuncionarios > 0 && $totalIfood > 0) {
            $avgIfoodPorFuncionario = $totalIfood / ($totalFuncionarios - $totalInativos);
        }

        // 7) Média do número de passagens por funcionário (VR)
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

        // A) Funcionários na Solides mas sem dados de VR
        $funcSemVR = DB::table('employees as e')
            ->join('companies as c', 'c.id', '=', 'e.company_id')
            ->whereNotNull('e.cod_solides')
            ->where(function ($q) {
                $q->whereNull('e.cod_vr')
                  ->orWhere('e.cod_vr', '');
            })
            ->where('e.active', true)
            ->select(
                'e.id',
                'e.full_name',
                'e.cod_solides',
                'e.cod_vr',
                'c.name as company_name'
            )
            ->orderBy('c.name')
            ->orderBy('e.full_name')
            ->get();

        // B) Top 10 empresas com MAIOR média de presença
        $topEmpresasPresencaMaior = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->join('companies as c', 'c.id', '=', 'e.company_id')
            ->whereDate('w.date', $refDate)
            ->where('e.active', true)
            ->groupBy('c.id', 'c.name')
            ->select(
                'c.id as company_id',
                'c.name as company_name',
                DB::raw('ROUND(
                    LEAST(SUM(w.calc_days), SUM(w.business_days))
                    / NULLIF(SUM(w.business_days), 0) * 100
                , 2) as avg_presence')
            )
            ->havingRaw('NULLIF(SUM(w.business_days), 0) IS NOT NULL')
            ->orderByDesc('avg_presence')
            ->limit(10)
            ->get();

        // C) Top 10 empresas com MENOR média de presença
        $topEmpresasPresencaMenor = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->join('companies as c', 'c.id', '=', 'e.company_id')
            ->whereDate('w.date', $refDate)
            ->where('e.active', true)
            ->groupBy('c.id', 'c.name')
            ->select(
                'c.id as company_id',
                'c.name as company_name',
                DB::raw('ROUND(
                    LEAST(SUM(w.calc_days), SUM(w.business_days))
                    / NULLIF(SUM(w.business_days), 0) * 100
                , 2) as avg_presence')
            )
            ->havingRaw('NULLIF(SUM(w.business_days), 0) IS NOT NULL')
            ->orderBy('avg_presence')
            ->limit(10)
            ->get();


        // D) Funcionários com gasto de benefício > limite
        $limiteBeneficioAlto = 500; // depois virá de config

        $funcionariosBeneficioAlto = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->join('companies as c', 'c.id', '=', 'e.company_id')
            ->whereDate('m.date', $refDate)
            ->where('e.active', true)
            ->groupBy('e.id', 'e.full_name', 'c.name')
            ->select(
                'e.id',
                'e.full_name',
                'c.name as company_name',
                DB::raw('SUM(m.total_value) as total_beneficios')
            )
            ->having('total_beneficios', '>', $limiteBeneficioAlto)
            ->orderByDesc('total_beneficios')
            ->get();

        // Arredonda melhor para exibição
        $fmt = fn ($v) => is_null($v) ? 0 : (float) $v;

        return view('dashboard', [
            'refMes'   => $refMes,
            'mesAtual' => $mesAtual,
            'meses'    => $meses,
            'anos'     => $anos,
            'anoAtual' => $anoSelecionado,

            'funcsDiasDiferentes'        => $funcsDiasDiferentes,
            'totalBeneficios'            => $fmt($totalBeneficios),
            'totalIfood'                 => $fmt($totalIfood),
            'avgBeneficioPorFuncionario' => $fmt($avgBeneficioPorFuncionario),
            'avgIfoodPorFuncionario'     => $fmt($avgIfoodPorFuncionario),
            'avgPassagensPorFuncionario' => $fmt($avgPassagensPorFuncionario),
            'totalFuncionarios'          => $totalFuncionarios,
            'totalInativos'              => $totalInativos,
            'topBeneficios'              => $topBeneficios,

            'funcSemVR'                  => $funcSemVR,
            'topEmpresasPresencaMaior'   => $topEmpresasPresencaMaior,
            'topEmpresasPresencaMenor'   => $topEmpresasPresencaMenor,
            'funcionariosBeneficioAlto'  => $funcionariosBeneficioAlto,
            'limiteBeneficioAlto'        => $limiteBeneficioAlto,
        ]);
    }
}
