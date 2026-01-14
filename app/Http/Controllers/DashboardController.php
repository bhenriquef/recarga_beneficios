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
        $inicio = Carbon::createFromDate($anoSelecionado, $mesSelecionado, 1)->startOfDay();
        $fim    = $inicio->copy()->addMonth()->endOfMonth()->endOfDay();

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
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->join('employees as e', 'eb.employee_id', '=', 'e.id')
            // ->where('e.active', 1)
            ->whereDate('m.date', $refDate)
            ->whereNotIn('b.cod', ['MOBILIDADE', 'IFOOD', 'VALE_ALIMENTACAO'])
            ->selectRaw('sum(m.total_value) as total_calculado, sum(m.saved_value) as total_economizado, sum(m.final_value) as total_real')
            ->first();

        $totalReal = $totalBeneficios->total_real;
        $totalEconomizado = $totalBeneficios->total_economizado;
        $totalBeneficios = $totalBeneficios->total_calculado;

        $totalValeAlimentacao = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->join('employees as e', 'eb.employee_id', '=', 'e.id')
            // ->where('e.active', 1)
            ->whereDate('m.date', $refDate)
            ->where('b.cod', 'VALE_ALIMENTACAO')
            ->sum('m.total_value');

        $totalMobilidadeIfood = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->join('employees as e', 'eb.employee_id', '=', 'e.id')
            // ->where('e.active', 1)
            ->whereDate('m.date', $refDate)
            ->where('b.cod', 'MOBILIDADE')
            ->sum('m.total_value');

        $totalTransporteIfood = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->join('employees as e', 'eb.employee_id', '=', 'e.id')
            // ->where('e.active', 1)
            ->whereDate('m.date', $refDate)
            ->where('b.cod', 'IFOOD')
            ->sum('m.total_value');

        // 3.2) Funcionários demitidos no período (shutdown_date)
        $totalDemitidosMes = Employee::whereNotNull('shutdown_date')
            ->whereBetween('shutdown_date', [$inicio->copy()->startOfMonth()->format('Y-m-d'), $fim->copy()->subMonth(1)->endOfMonth()->format('Y-m-d')])
            ->count();

        // 5) Média de benefício por funcionário
        $avgBeneficioPorFuncionario = DB::query()
            ->fromSub(function ($sub) use ($refDate) {
                $sub->from('employees_benefits_monthly as m')
                    ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
                    ->join('employees as e', 'eb.employee_id', '=', 'e.id')
                    ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
                    ->whereNotIn('b.cod', ['MOBILIDADE', 'IFOOD', 'VALE_ALIMENTACAO'])
                    ->whereDate('m.date', $refDate)
                    // ->where('e.active', true)
                    ->selectRaw('eb.employee_id, SUM(m.total_value) as total_por_func')
                    ->groupBy('eb.employee_id');
            }, 't')
            ->avg('total_por_func');

        // 6) Média de iFood por funcionário
        $avgIfoodPorFuncionario = 0;
        if ($totalFuncionarios > 0 && $totalValeAlimentacao > 0) {
            $avgIfoodPorFuncionario = $totalValeAlimentacao / ($totalFuncionarios - $totalInativos);
        }

        // 7) Média do número de passagens por funcionário (VR)
        $avgPassagensPorFuncionario = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'eb.employee_id', '=', 'e.id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->whereDate('m.date', $refDate)
            // ->where('e.active', true)
            ->avg('m.qtd');

        // Top 10 benefícios mais utilizados no mês
        $topBeneficios = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'eb.employee_id', '=', 'e.id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->whereDate('m.date', $refDate)
            // ->where('e.active', true)
            ->whereNotIn('b.cod', ['MOBILIDADE', 'IFOOD', 'VALE_ALIMENTACAO'])
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
            // ->where('e.active', true)
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
            // ->where('e.active', true)
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
            // ->where('e.active', true)
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

        // E) Gastos por empresa (MOBILIDADE / IFOOD / total_value / accumulated_value / final_value)
        $gastosPorEmpresa = DB::table('companies as c')
            ->leftJoin('employees as e', 'e.company_id', '=', 'c.id')
            ->leftJoin('employees_benefits as eb', 'eb.employee_id', '=', 'e.id')
            ->leftJoin('employees_benefits_monthly as m', function ($join) use ($refDate) {
                $join->on('m.employee_benefit_id', '=', 'eb.id')
                    ->whereDate('m.date', $refDate);
            })
            ->leftJoin('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->where('e.active', 1)
            ->groupBy('c.id', 'c.name')
            ->select(
                'c.id as company_id',
                'c.name as company_name',

                DB::raw("SUM(CASE WHEN b.cod = 'MOBILIDADE' THEN m.total_value END) as mobilidade_total"),
                DB::raw("SUM(CASE WHEN b.cod = 'MOBILIDADE' AND m.total_value IS NOT NULL THEN 1 ELSE 0 END) as mobilidade_cnt"),

                DB::raw("SUM(CASE WHEN b.cod = 'IFOOD' THEN m.total_value END) as ifood_vt_total"),
                DB::raw("SUM(CASE WHEN b.cod = 'IFOOD' AND m.total_value IS NOT NULL THEN 1 ELSE 0 END) as ifood_vt_cnt"),

                DB::raw("SUM(m.total_value) as valor_calculado"),
                DB::raw("SUM(CASE WHEN m.total_value IS NOT NULL THEN 1 ELSE 0 END) as valor_calculado_cnt"),

                DB::raw("SUM(m.accumulated_value) as valor_economizado"),
                DB::raw("SUM(CASE WHEN m.accumulated_value IS NOT NULL THEN 1 ELSE 0 END) as valor_economizado_cnt"),

                DB::raw("SUM(m.final_value) as valor_recarga"),
                DB::raw("SUM(CASE WHEN m.final_value IS NOT NULL THEN 1 ELSE 0 END) as valor_recarga_cnt")
            )
            ->havingRaw("
                SUM(CASE WHEN b.cod = 'MOBILIDADE' AND m.total_value IS NOT NULL THEN 1 ELSE 0 END) > 0
                OR SUM(CASE WHEN b.cod = 'IFOOD' AND m.total_value IS NOT NULL THEN 1 ELSE 0 END) > 0
                OR SUM(CASE WHEN m.total_value IS NOT NULL THEN 1 ELSE 0 END) > 0
                OR SUM(CASE WHEN m.accumulated_value IS NOT NULL THEN 1 ELSE 0 END) > 0
                OR SUM(CASE WHEN m.final_value IS NOT NULL THEN 1 ELSE 0 END) > 0
            ")
            ->orderByDesc('valor_recarga')
            ->get();

        // F) Funcionários demitidos com perdas estimadas
        $demitidos = DB::table('employees as e')
            ->join('companies as c', 'c.id', '=', 'e.company_id')
            ->whereNotNull('e.shutdown_date')
            ->whereBetween('e.shutdown_date', [$inicio->copy()->startOfMonth()->format('Y-m-d'), $fim->copy()->subMonth(1)->endOfMonth()->format('Y-m-d')])
            ->select('e.id', 'e.full_name', 'e.shutdown_date', 'c.name as company_name')
            ->orderByDesc('e.shutdown_date')
            ->get();

        $demitidosComPerda = $demitidos->map(function ($emp) use ($refDate) {
            $shutdown = Carbon::parse($emp->shutdown_date)->startOfDay();

            // fim do mês da demissão (calendário)
            $endOfMonth = $shutdown->copy()->endOfMonth()->startOfDay();

            // conta dias úteis Seg–Sáb a partir do dia seguinte à demissão
            $cursor = $shutdown->copy()->addDay();
            $diasUteisRestantes = 0;

            while ($cursor->lte($endOfMonth)) {
                // Carbon: Sunday = 0 (ou isSunday())
                if (!$cursor->isSunday()) {
                    $diasUteisRestantes++;
                }
                $cursor->addDay();
            }

            // Soma do "value" no mês de referência (refDate) para esse funcionário
            // (um funcionário pode ter vários benefícios)
            $benef = DB::table('employees_benefits_monthly as m')
                ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
                ->whereDate('m.date', $refDate)
                ->where('eb.employee_id', $emp->id)
                ->selectRaw('SUM(m.value) as soma_value, SUM(CASE WHEN m.value IS NOT NULL THEN 1 ELSE 0 END) as cnt_value')
                ->first();

            $somaValue = $benef?->soma_value;
            $cntValue  = (int) ($benef?->cnt_value ?? 0);

            return (object) [
                'id' => $emp->id,
                'company_name' => $emp->company_name,
                'full_name' => $emp->full_name,
                'shutdown_date' => $shutdown->format('d/m/Y'),
                'dias_uteis_restantes' => $diasUteisRestantes,

                'value_base' => $cntValue > 0 ? (float) $somaValue : null,
                'perda_estimada' => $cntValue > 0 ? ((float) $somaValue * $diasUteisRestantes) : null,
            ];
        });

        $demitidosComPerda = $demitidosComPerda
        ->filter(fn ($row) => !is_null($row->value_base))
        ->values();

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
            'totalReal'                  => $fmt($totalReal),
            'totalEconomizado'           => $fmt($totalEconomizado),
            'totalValeAlimentacao'                 => $fmt($totalValeAlimentacao),
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
            'totalMobilidadeIfood' => $fmt($totalMobilidadeIfood),
            'totalDemitidosMes'    => $totalDemitidosMes,
            'totalTransporteIfood' => $totalTransporteIfood,
            'gastosPorEmpresa' => $gastosPorEmpresa,
            'demitidosComPerda' => $demitidosComPerda,
        ]);
    }
}
