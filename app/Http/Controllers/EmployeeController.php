<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Carbon;

class EmployeeController extends Controller
{

    public function index(Request $request)
    {
        $search            = $request->input('search');
        $onlyActive        = $request->input('only_active');          // '', '1', '0'
        $onlyWithBenefits  = $request->boolean('only_with_benefits'); // checkbox
        $onlyWithSolides   = $request->boolean('only_with_solides');  // checkbox
        $onlyWithVr        = $request->boolean('only_with_vr');       // checkbox

        $employees = Employee::query()
            ->with('company')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('cod_solides', 'like', "%{$search}%")
                    ->orWhere('cod_vr', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
                });
            })
            // filtro por ativos/inativos
            ->when($onlyActive !== null && $onlyActive !== '', function ($query) use ($onlyActive) {
                $query->where('active', $onlyActive === '1');
            })
            // somente quem tem código Solides
            ->when($onlyWithSolides, function ($query) {
                $query->whereNotNull('cod_solides')
                    ->where('cod_solides', '!=', '');
            })
            // somente quem tem código VR
            ->when($onlyWithVr, function ($query) {
                $query->whereNotNull('cod_vr')
                    ->where('cod_vr', '!=', '');
            })
            // somente funcionários que possuem benefícios
            ->when($onlyWithBenefits, function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('employees_benefits as eb')
                        ->whereColumn('eb.employee_id', 'employees.id');
                });
            })
            ->orderBy('full_name')
            ->paginate(15)
            ->appends($request->query());

        return view('employees.index', compact(
            'employees',
            'search',
            'onlyActive',
            'onlyWithBenefits',
            'onlyWithSolides',
            'onlyWithVr'
        ));
    }
    public function show(Request $request, $id)
    {
        $employee = Employee::with('company')->findOrFail($id);

        // ================== PERÍODO (meses) ==================
        $today = Carbon::today();

        $startInput = $request->get('start'); // Y-m
        $endInput   = $request->get('end');   // Y-m

        try {
            if ($startInput && $endInput) {
                $periodStart = Carbon::createFromFormat('Y-m', $startInput)->startOfMonth();
                $periodEnd   = Carbon::createFromFormat('Y-m', $endInput)->endOfMonth();
            } else {
                $maxWorkdayDate = DB::table('workdays')
                    ->where('employee_id', $id)
                    ->max('date');

                $maxBenefitsDate = DB::table('employees_benefits_monthly as m')
                    ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
                    ->where('eb.employee_id', $id)
                    ->max('m.date');

                $maxAbsenceDate = DB::table('absenteeism')
                    ->where('employee_id', $id)
                    ->max('end_date');

                $maxHolidayDate = DB::table('holidays')
                    ->where('employee_id', $id)
                    ->max('end_date');

                $maxDate = $maxWorkdayDate ?? $maxBenefitsDate ?? $maxAbsenceDate ?? $maxHolidayDate ?? $today->toDateString();

                $periodEnd   = Carbon::parse($maxDate)->endOfMonth();
                $periodStart = $periodEnd->copy()->subMonths(5)->startOfMonth(); // últimos 6 meses
            }
        } catch (\Exception $e) {
            $periodEnd   = $today->copy()->endOfMonth();
            $periodStart = $periodEnd->copy()->subMonths(5)->startOfMonth();
        }

        if ($periodStart->gt($periodEnd)) {
            [$periodStart, $periodEnd] = [
                $periodEnd->copy()->startOfMonth(),
                $periodStart->copy()->endOfMonth()
            ];
        }

        $startDateStr = $periodStart->format('Y-m-d');
        $endDateStr   = $periodEnd->format('Y-m-d');

        $periodLabel           = $periodStart->format('m/Y') . ' a ' . $periodEnd->format('m/Y');
        $periodStartMonthValue = $periodStart->format('Y-m');
        $periodEndMonthValue   = $periodEnd->format('Y-m');

        // ================== TEMPO DE EMPRESA ==================
        $tempoEmpresaTexto = null;
        if ($employee->admission_date) {
            if ($employee->active) {
                $tempoEmpresaTexto = Carbon::parse($employee->admission_date)
                    ->locale('pt_BR')
                    ->diffForHumans(null, true, false, 2); // ex: "2 anos 3 meses"
            } else {
                $tempoEmpresaTexto = 'Não trabalha mais na empresa';
            }
        }

        // ================== MÉTRICAS NO PERÍODO ==================

        // Média de dias úteis no período
        $mediaDiasTrabalhados = DB::table('workdays')
            ->where('employee_id', $id)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->avg('business_days') ?? 0;

        // Média de faltas no período
        $mediaFaltas = DB::table('workdays')
            ->where('employee_id', $id)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->selectRaw('AVG(GREATEST(business_days - calc_days, 0)) as media')
            ->value('media') ?? 0;

        $mediaBeneficiosRow = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->where('eb.employee_id', $id)
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->whereNotIn('b.cod', ['MOBILIDADE', 'IFOOD', 'VALE_ALIMENTACAO'])
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->whereNull('m.deleted_at')
            ->selectRaw('
                SUM(m.total_value) as total,
                COUNT(DISTINCT DATE_FORMAT(m.date, "%Y-%m")) as meses
            ')
            ->first();

        $mediaBeneficios = 0;
        if ($mediaBeneficiosRow && $mediaBeneficiosRow->meses > 0) {
            $mediaBeneficios = $mediaBeneficiosRow->total / $mediaBeneficiosRow->meses;
        }

        $mediaIfoodRow = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->where('eb.employee_id', $id)
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->where('b.cod', 'VALE_ALIMENTACAO')
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->whereNull('m.deleted_at')
            ->selectRaw('
                SUM(m.total_value) as total,
                COUNT(DISTINCT DATE_FORMAT(m.date, "%Y-%m")) as meses
            ')
            ->first();

        $mediaIfood = 0;
        if ($mediaIfoodRow && $mediaIfoodRow->meses > 0) {
            $mediaIfood = $mediaIfoodRow->total / $mediaIfoodRow->meses;
        }

        // Total benefícios no período
        $totalBeneficios = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->whereNotIn('b.cod', ['MOBILIDADE', 'IFOOD', 'VALE_ALIMENTACAO'])
            ->where('eb.employee_id', $id)
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->whereNull('m.deleted_at')
            ->sum('m.total_value') ?? 0;

        $totalIfood = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->where('b.cod', 'VALE_ALIMENTACAO')
            ->where('eb.employee_id', $id)
            ->whereNull('m.deleted_at')
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->sum('m.total_value') ?? 0;

        // Benefícios configurados (sem filtro de data)
        $beneficiosUsados = DB::table('employees_benefits as eb')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->where('eb.employee_id', $id)
            ->select('b.description', 'b.operator', 'eb.value', 'eb.qtd')
            ->get();

        $sub1 = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->where('eb.employee_id', $id)
            ->whereNull('m.deleted_at')
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->groupBy(DB::raw("DATE_FORMAT(m.date, '%m/%Y')"), 'eb.employee_id')
            ->selectRaw("
                eb.employee_id,
                DATE_FORMAT(m.date, '%m/%Y') as mes,

                SUM(CASE WHEN b.cod NOT IN ('IFOOD','MOBILIDADE','VALE_ALIMENTACAO')
                    THEN COALESCE(m.total_value, 0) ELSE 0 END) as total_beneficios,

                SUM(CASE WHEN b.cod NOT IN ('IFOOD','MOBILIDADE','VALE_ALIMENTACAO')
                    THEN COALESCE(m.saved_value, 0) ELSE 0 END) as total_economizado,

                SUM(CASE WHEN b.cod NOT IN ('IFOOD','MOBILIDADE','VALE_ALIMENTACAO')
                    THEN COALESCE(m.accumulated_value, 0) ELSE 0 END) as total_acumulado,

                SUM(CASE WHEN b.cod NOT IN ('IFOOD','MOBILIDADE','VALE_ALIMENTACAO')
                    THEN COALESCE(m.final_value, 0) ELSE 0 END) as total_real,

                SUM(CASE WHEN b.cod = 'IFOOD'
                    THEN COALESCE(m.total_value, 0) ELSE 0 END) as total_ifood,

                SUM(CASE WHEN b.cod = 'MOBILIDADE'
                    THEN COALESCE(m.total_value, 0) ELSE 0 END) as total_mobilidade,

                SUM(CASE WHEN b.cod = 'VALE_ALIMENTACAO'
                    THEN COALESCE(m.total_value, 0) ELSE 0 END) as total_vale_alimentacao
            ");

        $historico = DB::table('workdays as w')
            ->leftJoinSub($sub1, 'sub1', function ($join) {
                $join->on('sub1.employee_id', '=', 'w.employee_id')
                    ->on(DB::raw("sub1.mes"), '=', DB::raw("DATE_FORMAT(w.date, '%m/%Y')"));
            })
            ->where('w.employee_id', $id)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->groupBy(DB::raw("DATE_FORMAT(w.date, '%m/%Y')"), 'w.employee_id')
            ->orderByRaw("MIN(w.date) ASC")
            ->selectRaw("
                DATE_FORMAT(w.date, '%m/%Y') as mes,
                w.employee_id,
                ROUND(AVG(w.calc_days), 1) as dias_calculados,
                ROUND(AVG(w.worked_days), 1) as dias_trabalhados_mes_anterior,
                SUM(w.business_days) as dias_uteis,

                MAX(COALESCE(sub1.total_beneficios, 0)) as total_beneficios,
                MAX(COALESCE(sub1.total_economizado, 0)) as total_economizado,
                MAX(COALESCE(sub1.total_acumulado, 0)) as total_acumulado,
                MAX(COALESCE(sub1.total_real, 0)) as total_real,
                MAX(COALESCE(sub1.total_ifood, 0)) as total_ifood,
                MAX(COALESCE(sub1.total_mobilidade, 0)) as total_mobilidade,
                MAX(COALESCE(sub1.total_vale_alimentacao, 0)) as total_vale_alimentacao
            ")
            ->get();

        $historicoTabela = $historico->sortByDesc(function ($item) {
            return \Carbon\Carbon::createFromFormat('m/Y', $item->mes);
        })->values();

        // Presença média no período
        $presencaMedia = DB::table('workdays')
            ->where('employee_id', $id)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->selectRaw("
                ROUND(
                    AVG(
                        CASE WHEN business_days > 0
                            THEN LEAST(calc_days, business_days) / business_days * 100
                            ELSE 0
                        END
                    )
                , 1) as media_presenca
            ")
            ->value('media_presenca') ?? 0;

        // Ranking na empresa (com base no período)
        $rankingQuery = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $employee->company_id)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->selectRaw('w.employee_id, AVG(w.calc_days) as media_dias')
            ->groupBy('w.employee_id')
            ->orderBy('media_dias')
            ->get();

        $posicaoRanking = optional($rankingQuery->values()->search(fn($item) => $item->employee_id == $id))->valueOf() + 1;
        if (!$posicaoRanking) {
            $posicaoRanking = '-';
        }

        $totalFuncionariosEmpresa = Employee::where('company_id', $employee->company_id)
            ->where('active', true)
            ->count();

        // Benefícios acumulados (mantive acumulado geral)
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

        // ================== FALTAS NO PERÍODO ==================
        $faltasPeriodo = DB::table('absenteeism as a')
            ->where('a.employee_id', $id)
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('a.start_date', [$startDateStr, $endDateStr])
                ->orWhereBetween('a.end_date', [$startDateStr, $endDateStr])
                ->orWhere(function ($q2) use ($startDateStr, $endDateStr) {
                    $q2->where('a.start_date', '<', $startDateStr)
                        ->where('a.end_date', '>', $endDateStr);
                });
            })
            ->orderByDesc('a.start_date')
            ->get();

        // ================== FÉRIAS NO PERÍODO + FUTURAS ==================
        $feriasPeriodo = DB::table('holidays as h')
            ->where('h.employee_id', $id)
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('h.start_date', [$startDateStr, $endDateStr])
                ->orWhereBetween('h.end_date', [$startDateStr, $endDateStr])
                ->orWhere(function ($q2) use ($startDateStr, $endDateStr) {
                    $q2->where('h.start_date', '<', $startDateStr)
                        ->where('h.end_date', '>', $endDateStr);
                });
            })
            ->select('h.start_date', 'h.end_date', DB::raw("'No período' as categoria"))
            ->get();

        $feriasFuturas = DB::table('holidays as h')
            ->where('h.employee_id', $id)
            ->where('h.start_date', '>', $endDateStr)
            ->select('h.start_date', 'h.end_date', DB::raw("'Futura' as categoria"))
            ->get();

        $feriasLista = $feriasPeriodo->concat($feriasFuturas)
            ->sortByDesc('start_date')
            ->values();

        // ================== PERDA ESTIMADA (se demitido) ==================
        $perdaDemissao = null;
        $perdaDemissaoBeneficios = collect();

        if (!is_null($employee->shutdown_date)) {
            $shutdown = Carbon::parse($employee->shutdown_date)->startOfDay();

            // Mês calendário da demissão
            $startOfMonth = $shutdown->copy()->startOfMonth()->startOfDay();
            $endOfMonth   = $shutdown->copy()->endOfMonth()->startOfDay();

            // Dias úteis restantes (Seg–Sáb) a partir do dia seguinte à demissão
            $cursor = $shutdown->copy()->addDay();
            $diasUteisRestantes = 0;

            while ($cursor->lte($endOfMonth)) {
                if (!$cursor->isSunday()) {
                    $diasUteisRestantes++;
                }
                $cursor->addDay();
            }

            // Dias úteis do mês (Seg–Sáb) — usando sua função existente
            $diasUteisMes = calcularDiasUteisComSabado($startOfMonth, $endOfMonth);

            // Mês de referência para buscar os benefícios (aqui: mês da demissão)
            // Atenção: no seu banco, "m.date" parece ser a data do mês (ex: 2025-01-01).
            $refDate = $startOfMonth->copy(); // 1º dia do mês da demissão

            // Resumo (igual dashboard): soma do value e média work_days (se você usa isso em algum lugar)
            $benefResumo = DB::table('employees_benefits_monthly as m')
                ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
                ->whereDate('m.date', $refDate->format('Y-m-d'))
                ->where('eb.employee_id', $id)
                ->whereNull('m.deleted_at')
                ->selectRaw('
                    SUM(CASE WHEN m.final_value IS NOT NULL AND m.final_value <> 0 THEN m.final_value ELSE m.total_value END) as soma_value,
                    AVG(m.work_days) as dias_trabalhados
                ')
                ->first();

            $valueBase = (float) ($benefResumo?->soma_value ?? 0);
            $diasTrabalhados = (float) ($benefResumo?->dias_trabalhados ?? 0);

            // Detalhamento por benefício (pra explicar o "value_base")
            $perdaDemissaoBeneficios = DB::table('employees_benefits_monthly as m')
                ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
                ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
                ->whereDate('m.date', $refDate->format('Y-m-d'))
                ->where('eb.employee_id', $id)
                ->whereNull('m.deleted_at')
                ->selectRaw('
                    b.description,
                    b.operator,
                    b.cod,
                    m.total_value,
                    m.final_value,
                    m.work_days,
                    CASE
                        WHEN m.final_value IS NOT NULL AND m.final_value <> 0 THEN m.final_value
                        ELSE m.total_value
                    END as value_used
                ')
                ->orderBy('b.description')
                ->get();

            $valorPorDia = ($diasUteisMes > 0) ? ($valueBase / $diasUteisMes) : 0;
            $perdaEstimada = $valorPorDia * $diasUteisRestantes;

            $perdaDemissao = (object) [
                'shutdown_date' => $shutdown->format('d/m/Y'),
                'mes_referencia_label' => $refDate->format('m/Y'),
                'periodo_inicio' => $startOfMonth->format('d/m/Y'),
                'periodo_fim' => $endOfMonth->format('d/m/Y'),
                'dias_uteis_mes' => (int) $diasUteisMes,
                'dias_uteis_restantes' => (int) $diasUteisRestantes,
                'dias_trabalhados' => $diasTrabalhados, // opcional (só pra debug/insight)
                'value_base' => $valueBase,
                'valor_por_dia' => $valorPorDia,
                'perda_estimada' => (float) $perdaEstimada,
            ];

            // Se quiser “não mostrar” quando não tem base
            if ($perdaDemissao->value_base <= 0) {
                $perdaDemissao = null;
                $perdaDemissaoBeneficios = collect();
            }
        }

        return view('employees.show', compact(
            'perdaDemissao',
            'perdaDemissaoBeneficios',
            'employee',
            'tempoEmpresaTexto',
            'mediaDiasTrabalhados',
            'mediaFaltas',
            'mediaIfood',
            'mediaBeneficios',
            'totalIfood',
            'totalBeneficios',
            'beneficiosUsados',
            'historico',
            'historicoTabela',
            'presencaMedia',
            'posicaoRanking',
            'totalFuncionariosEmpresa',
            'beneficiosAcumulados',
            'periodLabel',
            'periodStartMonthValue',
            'periodEndMonthValue',
            'startDateStr',
            'endDateStr',
            'faltasPeriodo',
            'feriasLista'
        ));
    }

    public function filter(Request $request)
    {
        $employees = Employee::query()
            ->when($request->admission_start && $request->admission_end, function ($q) use ($request) {
                $start = Carbon::parse($request->admission_start)->startOfDay();
                $end = Carbon::parse($request->admission_end)->endOfDay();
                $q->whereBetween('admission_date', [$start, $end]);
            })
            ->get(['id', 'full_name', 'cpf']);

        return response()->json($employees);
    }

}
