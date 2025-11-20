<?php
namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use Illuminate\Support\Carbon;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search       = $request->input('search');
        $hasEmployees = $request->input('has_employees'); // '', '1', '0'

        $companies = Company::query()
            ->withCount('employees')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
                });
            })
            // filtro: empresas com ou sem funcionários
            ->when($hasEmployees !== null && $hasEmployees !== '', function ($query) use ($hasEmployees) {
                if ($hasEmployees === '1') {
                    $query->having('employees_count', '>', 0);
                } elseif ($hasEmployees === '0') {
                    $query->having('employees_count', '=', 0);
                }
            })
            ->orderByDesc('employees_count')
            ->orderBy('name')
            ->paginate(10)
            ->appends($request->query());

        return view('companies.index', compact(
            'companies',
            'search',
            'hasEmployees'
        ));
    }

    public function show(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        // ---------------------------
        // PERÍODO (mês inicial / final)
        // ---------------------------
        $today = Carbon::today();

        $startInput = $request->get('start'); // formato esperado: Y-m
        $endInput   = $request->get('end');   // formato esperado: Y-m

        try {
            if ($startInput && $endInput) {
                $periodStart = Carbon::createFromFormat('Y-m', $startInput)->startOfMonth();
                $periodEnd   = Carbon::createFromFormat('Y-m', $endInput)->endOfMonth();
            } else {
                // Default: últimos 6 meses considerando maior data em workdays / benefits
                $maxWorkdayDate = DB::table('workdays as w')
                    ->join('employees as e', 'e.id', '=', 'w.employee_id')
                    ->where('e.company_id', $id)
                    ->max('w.date');

                $maxBenefitsDate = DB::table('employees_benefits_monthly as m')
                    ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
                    ->join('employees as e', 'e.id', '=', 'eb.employee_id')
                    ->where('e.company_id', $id)
                    ->max('m.date');

                $maxDate = $maxWorkdayDate ?? $maxBenefitsDate ?? $today->toDateString();

                $periodEnd = Carbon::parse($maxDate)->endOfMonth();
                $periodStart = $periodEnd->copy()->subMonths(5)->startOfMonth();
            }
        } catch (\Exception $e) {
            // Qualquer problema no parse, cai no default
            $periodEnd   = $today->copy()->endOfMonth();
            $periodStart = $periodEnd->copy()->subMonths(5)->startOfMonth();
        }

        // Garante que start <= end
        if ($periodStart->gt($periodEnd)) {
            [$periodStart, $periodEnd] = [$periodEnd->copy()->startOfMonth(), $periodStart->copy()->endOfMonth()];
        }

        $startDateStr = $periodStart->format('Y-m-d');
        $endDateStr   = $periodEnd->format('Y-m-d');

        $periodLabel  = $periodStart->format('m/Y') . ' a ' . $periodEnd->format('m/Y');
        $periodStartMonthValue = $periodStart->format('Y-m'); // para <input type="month">
        $periodEndMonthValue   = $periodEnd->format('Y-m');

        // ---------------------------
        // MÉTRICAS BÁSICAS
        // ---------------------------

        $totalFuncionarios = Employee::where('company_id', $id)
            ->where('active', true)
            ->count();

        $funcionariosInativos = Employee::where('company_id', $id)
            ->where('active', false)
            ->count();

        // Média de presença geral no período (apenas funcionários ativos)
        $mediaPresencaGeral = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->where('e.active', true)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->selectRaw("
                ROUND(
                    AVG(
                        CASE
                            WHEN w.business_days > 0
                            THEN LEAST(w.calc_days, w.business_days) / w.business_days * 100
                            ELSE 0
                        END
                    )
                , 1) as media
            ")
            ->value('media') ?? 0;

        // TOTAL de faltas no período (soma das faltas)
        $totalFaltasPeriodo = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->selectRaw("
                SUM(GREATEST(w.business_days - w.calc_days, 0)) as total_faltas
            ")
            ->value('total_faltas') ?? 0;

        // Benefícios — total e média no período
        $beneficios = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->where('e.company_id', $id)
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->selectRaw('
                e.company_id,
                SUM(m.total_value) as total,
                ROUND(AVG(m.total_value), 2) as media
            ')
            ->groupBy('e.company_id')
            ->first();

        // iFood — total e média no período
        $ifood = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->selectRaw('
                e.company_id,
                SUM(w.calc_days * 10) as total,
                ROUND(AVG(w.calc_days * 10), 2) as media
            ')
            ->groupBy('e.company_id')
            ->first();

        // Histórico mensal consolidado (dentro do período)
        $historico = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
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
                    AND m2.date BETWEEN '{$startDateStr}' AND '{$endDateStr}'
                    AND DATE_FORMAT(m2.date, '%m/%Y') = DATE_FORMAT(w.date, '%m/%Y')
                ) as total_beneficios
            ")
            ->groupBy('mes', 'e.company_id', 'w.date')
            ->orderByRaw("w.date ASC")
            ->get();

        $historicoTabela = $historico->sortByDesc(function ($item) {
            return \Carbon\Carbon::createFromFormat('m/Y', $item->mes);
        })->values();

        // Top funcionário no período (maior média de dias trabalhados)
        $topFuncionario = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->selectRaw('e.full_name, ROUND(AVG(w.calc_days),1) as media_dias')
            ->groupBy('e.id', 'e.full_name')
            ->orderByDesc('media_dias')
            ->first();

        // Benefícios detalhados no período
        $beneficiosDetalhados = DB::table('employees_benefits_monthly as m')
            ->join('employees_benefits as eb', 'eb.id', '=', 'm.employee_benefit_id')
            ->join('benefits as b', 'b.id', '=', 'eb.benefits_id')
            ->join('employees as e', 'e.id', '=', 'eb.employee_id')
            ->where('e.company_id', $id)
            ->whereBetween('m.date', [$startDateStr, $endDateStr])
            ->select('b.description as beneficio', DB::raw('SUM(m.total_value) as total'))
            ->groupBy('b.id', 'b.description')
            ->get();

        // Total iFood no período (para incluir no gráfico de custo por benefício)
        $totalIfood = DB::table('workdays as w')
            ->join('employees as e', 'e.id', '=', 'w.employee_id')
            ->where('e.company_id', $id)
            ->whereBetween('w.date', [$startDateStr, $endDateStr])
            ->sum(DB::raw('w.calc_days * 10'));

        $beneficiosDetalhados->push((object)[
            'beneficio' => 'iFood',
            'total' => $totalIfood,
        ]);

    // ---------------------------------
    // TABELA ÚNICA: RESUMO POR FUNCIONÁRIO + VR
    // ---------------------------------
    $funcionariosResumo = DB::table('employees as e')
        ->leftJoin('workdays as w', function ($join) use ($startDateStr, $endDateStr) {
            $join->on('w.employee_id', '=', 'e.id')
                ->whereBetween('w.date', [$startDateStr, $endDateStr]);
        })
        ->leftJoin('employees_benefits as eb', 'eb.employee_id', '=', 'e.id')
        ->leftJoin('employees_benefits_monthly as m', function ($join) use ($startDateStr, $endDateStr) {
            $join->on('m.employee_benefit_id', '=', 'eb.id')
                ->whereBetween('m.date', [$startDateStr, $endDateStr]);
        })
        ->where('e.company_id', $id)
        ->selectRaw("
            e.id,
            e.full_name,
            e.active,
            e.cod_solides,
            e.cod_vr,

            COALESCE(SUM(m.total_value), 0) as total_beneficios,
            COALESCE(SUM(w.calc_days * 10), 0) as total_ifood,
            COALESCE(SUM(GREATEST(w.business_days - w.calc_days, 0)), 0) as total_faltas,

            ROUND(
                CASE
                    WHEN SUM(w.business_days) > 0
                    THEN LEAST(SUM(w.calc_days), SUM(w.business_days)) / SUM(w.business_days) * 100
                    ELSE 0
                END
            , 2) as perc_presenca,

            CASE
                WHEN e.cod_vr IS NULL OR e.cod_vr = '' THEN 'Não cadastrado'
                ELSE 'Ativo'
            END as status_vr
        ")
        ->groupBy('e.id', 'e.full_name', 'e.active', 'e.cod_solides', 'e.cod_vr')
        ->orderBy('e.full_name')
        ->get();


        // ---------------------------------
        // LISTA DE FÉRIAS / FALTAS NO PERÍODO
        // ---------------------------------
        $feriasPeriodo = DB::table('holidays as h')
            ->join('employees as e', 'e.id', '=', 'h.employee_id')
            ->where('e.company_id', $id)
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('h.start_date', [$startDateStr, $endDateStr])
                ->orWhereBetween('h.end_date', [$startDateStr, $endDateStr])
                ->orWhere(function ($q2) use ($startDateStr, $endDateStr) {
                    $q2->where('h.start_date', '<', $startDateStr)
                        ->where('h.end_date', '>', $endDateStr);
                });
            })
            ->select(
                'e.id as employee_id',
                'e.full_name',
                'h.start_date',
                'h.end_date',
                DB::raw("'Férias' as tipo"),
                DB::raw('NULL as reason')
            )
            ->get();

        $faltasPeriodo = DB::table('absenteeism as a')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->where('e.company_id', $id)
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('a.start_date', [$startDateStr, $endDateStr])
                ->orWhereBetween('a.end_date', [$startDateStr, $endDateStr])
                ->orWhere(function ($q2) use ($startDateStr, $endDateStr) {
                    $q2->where('a.start_date', '<', $startDateStr)
                        ->where('a.end_date', '>', $endDateStr);
                });
            })
            ->select(
                'e.id as employee_id',
                'e.full_name',
                'a.start_date',
                'a.end_date',
                DB::raw("'Falta / Afastamento' as tipo"),
                'a.reason'
            )
            ->get();

        $eventosPeriodo = $feriasPeriodo->concat($faltasPeriodo)
            ->sortByDesc('start_date')
            ->values();

        return view('companies.show', compact(
            'company',
            'totalFuncionarios',
            'funcionariosInativos',
            'mediaPresencaGeral',
            'totalFaltasPeriodo',
            'beneficios',
            'ifood',
            'historico',
            'historicoTabela',
            'topFuncionario',
            'beneficiosDetalhados',
            'periodLabel',
            'periodStartMonthValue',
            'periodEndMonthValue',
            'funcionariosResumo',
            'eventosPeriodo',
            'startDateStr',
            'endDateStr',
        ));
    }
}
