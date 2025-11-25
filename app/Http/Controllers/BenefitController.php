<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Benefit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BenefitController extends Controller
{
    public function index(Request $request)
    {
        $search        = $request->input('search');
        $hasEmployees  = $request->input('has_employees');          // '', '1', '0'
        $onlyVariable  = $request->boolean('only_variable');        // checkbox

        $benefits = Benefit::query()
            ->withCount('employees')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                    ->orWhere('cod', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('operator', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
                });
            })
            // filtro: benefícios com / sem funcionários
            ->when($hasEmployees !== null && $hasEmployees !== '', function ($query) use ($hasEmployees) {
                if ($hasEmployees === '1') {
                    $query->having('employees_count', '>', 0);
                } elseif ($hasEmployees === '0') {
                    $query->having('employees_count', '=', 0);
                }
            })
            // somente benefícios variáveis
            ->when($onlyVariable, function ($query) {
                $query->where('variable', true);
            })
            ->orderByDesc('employees_count')
            ->orderBy('description')
            ->paginate(10)
            ->appends($request->query());

        return view('benefits.index', compact(
            'benefits',
            'search',
            'hasEmployees',
            'onlyVariable'
        ));
    }

    public function show(Request $request, $benefitId)
    {
        $benefit = Benefit::findOrFail($benefitId);

        /** -------------------------------------------------
         * 0) Descobrir range de datas disponível para o benefício
         * ------------------------------------------------- */
        $range = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->where('eb.benefits_id', $benefitId)
            ->selectRaw('MIN(m.date) as min_date, MAX(m.date) as max_date')
            ->first();

        $minDate = $range && $range->min_date ? Carbon::parse($range->min_date)->startOfMonth() : Carbon::today()->subYear();
        $maxDate = $range && $range->max_date ? Carbon::parse($range->max_date)->endOfMonth()   : Carbon::today()->endOfMonth();

        // Valores vindos da UI: YYYY-MM
        $startInput = $request->query('start');
        $endInput   = $request->query('end');

        if ($startInput) {
            try {
                $start = Carbon::createFromFormat('Y-m', $startInput)->startOfMonth();
            } catch (\Exception $e) {
                $start = $minDate->copy();
            }
        } else {
            // Padrão: últimos 6 meses (ou desde o início se tiver menos)
            $start = $maxDate->copy()->subMonths(5)->startOfMonth();
        }

        if ($endInput) {
            try {
                $end = Carbon::createFromFormat('Y-m', $endInput)->endOfMonth();
            } catch (\Exception $e) {
                $end = $maxDate->copy();
            }
        } else {
            $end = $maxDate->copy();
        }

        // Garante que está dentro do range de dados
        if ($start->lt($minDate)) {
            $start = $minDate->copy();
        }
        if ($end->gt($maxDate)) {
            $end = $maxDate->copy();
        }

        // Se por algum motivo start > end, inverte
        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $startDateStr = $start->toDateString();
        $endDateStr   = $end->toDateString();

        $periodLabel            = $start->format('m/Y') . ' até ' . $end->format('m/Y');
        $periodStartMonthValue  = $start->format('Y-m');
        $periodEndMonthValue    = $end->format('Y-m');

        /**
         * 1) Séries mensais básicas (tudo ASC para gráficos)
         */
        $historicoMensal = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->where('eb.benefits_id', $benefitId)
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->selectRaw("
                DATE_FORMAT(m.date, '%m/%Y') as mes,
                CAST(SUM(m.total_value) AS DECIMAL(12,2)) as total_beneficio,
                COUNT(DISTINCT e.id) as qtd_func,
                CAST(AVG(m.total_value) AS DECIMAL(12,2)) as media_func
            ")
            ->groupBy('mes')
            ->orderByRaw("STR_TO_DATE(mes, '%m/%Y') ASC")
            ->get();

        $todosBeneficiosMensal = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->selectRaw("
                DATE_FORMAT(m.date, '%m/%Y') as mes,
                CAST(SUM(m.total_value) AS DECIMAL(12,2)) as total_beneficios
            ")
            ->groupBy('mes')
            ->orderByRaw("STR_TO_DATE(mes, '%m/%Y') ASC")
            ->get();

        $ifoodMensal = DB::table('workdays as w')
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->selectRaw("
                DATE_FORMAT(w.date, '%m/%Y') as mes,
                CAST(SUM(w.calc_days * 10) AS DECIMAL(12,2)) as total_ifood
            ")
            ->groupBy('mes')
            ->orderByRaw("STR_TO_DATE(mes, '%m/%Y') ASC")
            ->get();

        /**
         * 2) Média de dias trabalhados dos beneficiários (por mês)
         */
        $diasTrabalhadosMensal = DB::table('employees_benefits as eb')
            ->join('employees_benefits_monthly as m', 'm.employee_benefit_id', '=', 'eb.id')
            ->join('workdays as w', function ($j) {
                $j->on('w.employee_id', '=', 'eb.employee_id')
                ->whereRaw("DATE_FORMAT(w.date, '%m/%Y') = DATE_FORMAT(m.date, '%m/%Y')");
            })
            ->where('eb.benefits_id', $benefitId)
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->selectRaw("
                DATE_FORMAT(m.date, '%m/%Y') as mes,
                CAST(AVG(w.calc_days) AS DECIMAL(10,2)) as media_dias
            ")
            ->groupBy('mes')
            ->orderByRaw("STR_TO_DATE(mes, '%m/%Y') ASC")
            ->get();

        // Mapas por mês
        $mapBen   = $historicoMensal->keyBy('mes');
        $mapTot   = $todosBeneficiosMensal->keyBy('mes');
        $mapIfo   = $ifoodMensal->keyBy('mes');
        $mapDias  = $diasTrabalhadosMensal->keyBy('mes');

        // Meses combinados (ASC)
        $meses = collect(array_unique(array_merge(
            $historicoMensal->pluck('mes')->all(),
            $todosBeneficiosMensal->pluck('mes')->all(),
            $ifoodMensal->pluck('mes')->all(),
            $diasTrabalhadosMensal->pluck('mes')->all(),
        )))->sort(function ($a, $b) {
            $da = \Carbon\Carbon::createFromFormat('m/Y', $a);
            $db = \Carbon\Carbon::createFromFormat('m/Y', $b);
            return $da <=> $db;
        })->values();

        // Série consolidada (ASC para gráficos)
        $historico = $meses->map(function ($mes) use ($mapBen, $mapTot, $mapIfo, $mapDias) {
            $total_beneficio   = (float)($mapBen[$mes]->total_beneficio ?? 0);
            $qtd_func          = (int)  ($mapBen[$mes]->qtd_func ?? 0);
            $media_func        = (float)($mapBen[$mes]->media_func ?? 0);
            $total_beneficios  = (float)($mapTot[$mes]->total_beneficios ?? 0);
            $total_ifood       = (float)($mapIfo[$mes]->total_ifood ?? 0);
            $media_dias        = (float)($mapDias[$mes]->media_dias ?? 0);
            $outros_beneficios = max($total_beneficios - $total_beneficio, 0);
            $custo_por_dia     = $media_dias > 0 ? $media_func / $media_dias : 0;

            return (object)[
                'mes' => $mes,
                'total_beneficio'   => $total_beneficio,
                'qtd_func'          => $qtd_func,
                'media_func'        => $media_func,
                'total_beneficios'  => $total_beneficios,
                'total_ifood'       => $total_ifood,
                'outros_beneficios' => $outros_beneficios,
                'media_dias'        => $media_dias,
                'custo_por_dia'     => $custo_por_dia,
            ];
        });

        // Tabela em DESC (mais recente primeiro)
        $historicoTabela = $historico
            ->sortByDesc(fn($h) => \Carbon\Carbon::createFromFormat('m/Y', $h->mes))
            ->values();

        /**
         * 3) Top 10 funcionários que mais receberam esse benefício NO PERÍODO
         */
        $topFuncionarios = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->where('eb.benefits_id', $benefitId)
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->select(
                'e.full_name',
                DB::raw('SUM(m.total_value) as total_recebido'),
                DB::raw('AVG(m.total_value) as media_mensal')
            )
            ->groupBy('e.id', 'e.full_name')
            ->orderByDesc('total_recebido')
            ->limit(10)
            ->get();

        /**
         * 4) Funcionários que possuem este benefício NO PERÍODO
         */
        $funcionariosBeneficio = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->where('eb.benefits_id', $benefitId)
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->select(
                'e.id',
                'e.full_name',
                'e.active',
                DB::raw('SUM(m.total_value) as total_beneficio')
            )
            ->groupBy('e.id', 'e.full_name', 'e.active')
            ->orderBy('e.full_name')
            ->get();

        /**
         * 5) Resumo (cards) – sempre considerando APENAS o período filtrado
         */
        $totalBeneficioPeriodo = $historico->sum('total_beneficio');
        $mediaPorFuncionario   = $historico->avg('media_func') ?: 0;
        $funcAtivosUltimoMes   = $historico->last()->qtd_func ?? 0;
        $custoMedioPorDia      = $historico->avg('custo_por_dia') ?: 0;
        $totalIfoodPeriodo     = $historico->sum('total_ifood');
        $totalTodosPeriodo     = $historico->sum('total_beneficios');
        $totalOutrosPeriodo    = max($totalTodosPeriodo - $totalBeneficioPeriodo, 0);
        $participacaoNoTotal   = $totalTodosPeriodo > 0
            ? round(($totalBeneficioPeriodo / $totalTodosPeriodo) * 100, 1)
            : 0;

        return view('benefits.show', compact(
            'benefit',
            'historico',          // ASC → gráficos
            'historicoTabela',    // DESC → tabela
            'topFuncionarios',
            'totalBeneficioPeriodo',
            'mediaPorFuncionario',
            'funcAtivosUltimoMes',
            'custoMedioPorDia',
            'totalIfoodPeriodo',
            'totalTodosPeriodo',
            'totalOutrosPeriodo',
            'participacaoNoTotal',
            'funcionariosBeneficio',
            // período
            'periodLabel',
            'startDateStr',
            'endDateStr',
            'periodStartMonthValue',
            'periodEndMonthValue',
        ));
    }
}
