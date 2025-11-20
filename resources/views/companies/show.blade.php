<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $company->name }} — Detalhes da Empresa
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto space-y-6">
        <div class="bg-white shadow rounded-lg p-6">

            {{-- 🏢 Informações da Empresa --}}
            <div class="mb-8 bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $company->name }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-700">
                    <p><strong>CNPJ:</strong> {{ $company->cnpj ?? 'Não informado' }}</p>
                    <p><strong>Cidade:</strong> {{ $company->city ?? '-' }}</p>
                    <p><strong>Estado:</strong> {{ $company->state ?? '-' }}</p>
                </div>
            </div>

            {{-- 🔎 Filtro de Período --}}
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <p class="text-sm text-gray-600">
                    Período analisado:
                    <strong>{{ $periodLabel }}</strong>
                    <span class="text-gray-400">(dados de {{ \Carbon\Carbon::parse($startDateStr)->format('d/m/Y') }}
                        a {{ \Carbon\Carbon::parse($endDateStr)->format('d/m/Y') }})</span>
                </p>

                <form method="GET" action="{{ route('companies.show', $company->id) }}" class="flex flex-wrap items-center gap-2">
                    <label class="text-xs text-gray-500 uppercase">Início</label>
                    <input
                        type="month"
                        name="start"
                        value="{{ $periodStartMonthValue }}"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >

                    <span class="text-xs text-gray-500 uppercase">Fim</span>
                    <input
                        type="month"
                        name="end"
                        value="{{ $periodEndMonthValue }}"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >

                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700"
                    >
                        Aplicar
                    </button>
                </form>
            </div>


            {{-- 📊 Cards de Métricas --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
                <x-stat-card label="Funcionários Ativos" :value="$totalFuncionarios" color="indigo" />
                <x-stat-card
                    label="Média de Presença (período)"
                    :value="number_format($mediaPresencaGeral ?? 0, 1, ',', '.').'%'"
                    color="green"
                />
                <x-stat-card
                    label="Total de Faltas (período)"
                    :value="number_format($totalFaltasPeriodo ?? 0, 0, ',', '.')"
                    color="rose"
                />
                <x-stat-card
                    label="Total VT (período)"
                    :value="'R$ '.number_format($beneficios->total ?? 0, 2, ',', '.')"
                    color="emerald"
                />
                <x-stat-card
                    label="Média VT (por lançamento)"
                    :value="'R$ '.number_format($beneficios->media ?? 0, 2, ',', '.')"
                    color="emerald"
                />
                <x-stat-card
                    label="Total VR (período)"
                    :value="'R$ '.number_format($ifood->total ?? 0, 2, ',', '.')" color="orange"
                />
                <x-stat-card
                    label="Média VR (por lançamento)"
                    :value="'R$ '.number_format($ifood->media ?? 0, 2, ',', '.')" color="orange"
                />
                <x-stat-card
                    label="Top Funcionário (período)"
                    :value="e($topFuncionario->full_name ?? '-')" color="blue" value-size="text-base" truncate="true"
                />
            </div>

            {{-- 📅 Histórico Mensal --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Histórico Mensal</h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Período: <strong>{{ $periodLabel }}</strong>
                    </p>

                    @if($historicoTabela->isNotEmpty())
                        <input
                            id="filter-historico"
                            type="text"
                            placeholder="Filtrar por mês..."
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    @endif
                </div>

                @if($historicoTabela->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum histórico encontrado para o período selecionado.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-historico" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Mês</th>
                                    <th class="px-3 py-2">Média Dias Úteis</th>
                                    <th class="px-3 py-2">Média Dias Trabalhados</th>
                                    <th class="px-3 py-2">Total VT</th>
                                    <th class="px-3 py-2">Total VR</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($historicoTabela as $h)
                                    <tr>
                                        <td class="px-3 py-2">{{ $h->mes }}</td>
                                        <td class="px-3 py-2">{{ $h->media_dias_uteis }}</td>
                                        <td class="px-3 py-2">{{ $h->media_dias_trabalhados }}</td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($h->total_beneficios, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($h->total_ifood, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <span id="historico-info"></span>
                        <div id="historico-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>


            {{-- 👥 Funcionários — Benefícios, iFood, Faltas, Presença e VR --}}
            <h3 class="text-lg font-semibold text-gray-700 mt-8 mb-3">
                Funcionários — VT, VR, Faltas, Presença e Status de VR
            </h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Período: <strong>{{ $periodLabel }}</strong>
                    </p>

                    @if($funcionariosResumo->isNotEmpty())
                        <input
                            id="filter-func-resumo"
                            type="text"
                            placeholder="Filtrar por nome, status VR..."
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    @endif
                </div>

                @if($funcionariosResumo->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum dado encontrado para o período selecionado.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-func-resumo" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Funcionário</th>
                                    <th class="px-3 py-2">Ativo?</th>
                                    <th class="px-3 py-2">Cód. Solides</th>
                                    <th class="px-3 py-2">Status VR</th>
                                    <th class="px-3 py-2">VT (R$)</th>
                                    <th class="px-3 py-2">VR (R$)</th>
                                    <th class="px-3 py-2">Faltas</th>
                                    <th class="px-3 py-2">% Presença</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($funcionariosResumo as $f)
                                    <tr>
                                        <td class="px-3 py-2">{{ $f->full_name }}</td>

                                        <td class="px-3 py-2">
                                            @if($f->active)
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                    Ativo
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                    Inativo
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-2">{{ $f->cod_solides ?? '—' }}</td>

                                        <td class="px-3 py-2">
                                            @if($f->status_vr === 'Ativo')
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                                    Ativo na VR
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                                    Não cadastrado
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-2">
                                            R$ {{ number_format($f->total_beneficios, 2, ',', '.') }}
                                        </td>

                                        <td class="px-3 py-2">
                                            R$ {{ number_format($f->total_ifood, 2, ',', '.') }}
                                        </td>

                                        <td class="px-3 py-2">
                                            {{ number_format($f->total_faltas, 0, ',', '.') }}
                                        </td>

                                        <td class="px-3 py-2">
                                            {{ number_format($f->perc_presenca, 2, ',', '.') }} %
                                        </td>

                                        <td class="px-3 py-2 text-right">
                                            <a
                                                href="{{ route('employees.show', $f->id) }}"
                                                class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700"
                                            >
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <span id="func-resumo-info"></span>
                        <div id="func-resumo-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>


            {{-- 📅 Funcionários com Férias / Faltas no Período --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">
                Férias e Faltas / Afastamentos no Período
            </h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Período: <strong>{{ $periodLabel }}</strong>
                    </p>
                    @if($eventosPeriodo->isNotEmpty())
                        <input
                            id="filter-eventos"
                            type="text"
                            placeholder="Filtrar por nome, tipo ou motivo..."
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    @endif
                </div>

                @if($eventosPeriodo->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma férias ou falta registrada no período selecionado.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-eventos" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Tipo</th>
                                    <th class="px-3 py-2">Funcionário</th>
                                    <th class="px-3 py-2">Início</th>
                                    <th class="px-3 py-2">Fim</th>
                                    <th class="px-3 py-2">Motivo</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($eventosPeriodo as $ev)
                                    <tr>
                                        <td class="px-3 py-2">{{ $ev->tipo }}</td>
                                        <td class="px-3 py-2">{{ $ev->full_name }}</td>
                                        <td class="px-3 py-2">
                                            {{ \Carbon\Carbon::parse($ev->start_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ \Carbon\Carbon::parse($ev->end_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ $ev->reason ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <a
                                                href="{{ route('employees.show', $ev->employee_id) }}"
                                                class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700"
                                            >
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <span id="eventos-info"></span>
                        <div id="eventos-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>

            {{-- 📈 Gráficos --}}
            <h3 class="text-lg font-semibold text-gray-700 mt-10 mb-3">Análises e Gráficos</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartBeneficiosIfood"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartDiasTrabalhados"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartCustoTotalMes" height="150"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartEvolucaoPresenca" height="150"></canvas>
                </div>

            </div>
            <div class="grid grid-cols-2 lg:grid-cols-1 gap-6 mt-4">
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartCustoPorBeneficio" height="200"></canvas>
                </div>
            </div>

            <div class="mt-8">
                <a href="{{ route('companies.index') }}"
                   class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                    Voltar
                </a>
            </div>
        </div>
    </div>

    {{-- Scripts dos Gráficos --}}
    <script>
        function initTableWithFilter(options) {
            const {
                tableId,
                filterInputId,
                infoId,
                paginationId,
                pageSize = 10,
            } = options;

            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const allRows = Array.from(tbody.querySelectorAll('tr'));
            let filteredRows = allRows.slice();
            let currentPage = 1;

            const infoEl = document.getElementById(infoId);
            const paginationEl = document.getElementById(paginationId);
            const filterInput = document.getElementById(filterInputId);

            function renderPagination(totalPages) {
                if (!paginationEl) return;
                paginationEl.innerHTML = '';

                if (totalPages <= 1) return;

                const maxNumericButtons = 5;

                function createBtn(page, label, { disabled = false, active = false } = {}) {
                    const btn = document.createElement('button');
                    btn.textContent = label;
                    btn.className =
                        'px-2 py-1 border rounded text-xs ' +
                        (active
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 hover:bg-gray-100') +
                        (disabled ? ' opacity-50 cursor-default' : '');

                    if (!disabled) {
                        btn.addEventListener('click', () => {
                            currentPage = page;
                            render();
                        });
                    }

                    return btn;
                }

                function createEllipsis() {
                    const span = document.createElement('span');
                    span.textContent = '...';
                    span.className = 'px-1 text-xs text-gray-400 select-none';
                    return span;
                }

                const totalPagesClamped = Math.max(1, totalPages);

                // «
                paginationEl.appendChild(
                    createBtn(Math.max(1, currentPage - 1), '«', { disabled: currentPage === 1 })
                );

                let startPage, endPage;
                if (totalPagesClamped <= maxNumericButtons) {
                    startPage = 1;
                    endPage = totalPagesClamped;
                } else {
                    startPage = Math.max(1, currentPage - 2);
                    endPage = Math.min(totalPagesClamped, currentPage + 2);

                    if (startPage === 1) {
                        endPage = maxNumericButtons;
                    } else if (endPage === totalPagesClamped) {
                        startPage = totalPagesClamped - maxNumericButtons + 1;
                    }
                }

                if (startPage > 1) {
                    paginationEl.appendChild(
                        createBtn(1, '1', { active: currentPage === 1 })
                    );
                    if (startPage > 2) {
                        paginationEl.appendChild(createEllipsis());
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationEl.appendChild(
                        createBtn(i, i.toString(), { active: currentPage === i })
                    );
                }

                if (endPage < totalPagesClamped) {
                    if (endPage < totalPagesClamped - 1) {
                        paginationEl.appendChild(createEllipsis());
                    }
                    paginationEl.appendChild(
                        createBtn(
                            totalPagesClamped,
                            totalPagesClamped.toString(),
                            { active: currentPage === totalPagesClamped }
                        )
                    );
                }

                // »
                paginationEl.appendChild(
                    createBtn(
                        Math.min(totalPagesClamped, currentPage + 1),
                        '»',
                        { disabled: currentPage === totalPagesClamped }
                    )
                );
            }

            function render() {
                const total = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));

                if (currentPage > totalPages) currentPage = totalPages;

                allRows.forEach(row => (row.style.display = 'none'));

                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;

                filteredRows.slice(start, end).forEach(row => {
                    row.style.display = '';
                });

                if (infoEl) {
                    const from = total ? start + 1 : 0;
                    const to = Math.min(end, total);
                    infoEl.textContent = `Mostrando ${from}–${to} de ${total} registro(s).`;
                }

                renderPagination(totalPages);
            }

            if (filterInput) {
                filterInput.addEventListener('input', () => {
                    const term = filterInput.value.toLowerCase();
                    filteredRows = allRows.filter(row =>
                        row.textContent.toLowerCase().includes(term)
                    );
                    currentPage = 1;
                    render();
                });
            }

            render();
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Histórico mensal da empresa
            initTableWithFilter({
                tableId: 'table-historico',
                filterInputId: 'filter-historico',
                infoId: 'historico-info',
                paginationId: 'historico-pagination',
                pageSize: 12,
            });

            // Funcionários — benefícios / iFood / faltas / presença / VR
            initTableWithFilter({
                tableId: 'table-func-resumo',
                filterInputId: 'filter-func-resumo',
                infoId: 'func-resumo-info',
                paginationId: 'func-resumo-pagination',
                pageSize: 10,
            });

            // Férias + faltas/afastamentos no período
            initTableWithFilter({
                tableId: 'table-eventos',
                filterInputId: 'filter-eventos',
                infoId: 'eventos-info',
                paginationId: 'eventos-pagination',
                pageSize: 10,
            });
        });

    </script>

    <script>
        const historico = @json($historico);
        const meses = historico.map(i => i.mes);
        const totalBeneficios = historico.map(i => i.total_beneficios);
        const totalIfood = historico.map(i => i.total_ifood);
        const diasTrabalhados = historico.map(i => i.media_dias_trabalhados);
        const diasUteis = historico.map(i => i.media_dias_uteis);
        const beneficiosDetalhados = @json($beneficiosDetalhados);

        new Chart(document.getElementById('chartCustoPorBeneficio').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: beneficiosDetalhados.map(b => b.beneficio),
                datasets: [{
                    data: beneficiosDetalhados.map(b => Number(b.total) || 0),
                    backgroundColor: [
                        '#10B981', // verde
                        '#3B82F6', // azul
                        '#F59E0B', // laranja
                        '#EF4444', // vermelho
                        '#8B5CF6', // roxo
                        '#14B8A6', // ciano
                        '#F97316', // laranja escuro
                        '#6366F1'  // índigo
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Custo Total por VT (incluindo VR)',
                        font: { size: 14 }
                    },
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = beneficiosDetalhados.reduce((acc, b) => acc + Number(b.total || 0), 0);
                                const value = context.raw;
                                const percent = ((value / total) * 100).toFixed(1);
                                return `${context.label}: R$ ${value.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} (${percent}%)`;
                            }
                        }
                    }
                }
            }
        });


        new Chart(document.getElementById('chartEvolucaoPresenca').getContext('2d'), {
            type: 'line',
            data: {
                labels: historico.map(h => h.mes),
                datasets: [{
                    label: 'Presença Média (%)',
                    data: historico.map(h => {
                        const uteis = Number(h.media_dias_uteis) || 0;
                        const trab  = Number(h.media_dias_trabalhados) || 0;
                        if (!uteis) return 0;
                        const perc = (trab / uteis) * 100;
                        return Math.min(100, perc.toFixed(1));
                    }),

                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                plugins: {
                    title: { display: true, text: 'Evolução da Presença Média da Empresa' },
                    legend: { display: false }
                },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });

        new Chart(document.getElementById('chartCustoTotalMes').getContext('2d'), {
            type: 'bar',
            data: {
                labels: historico.map(h => h.mes),
                datasets: [{
                    label: 'Custo Total (R$)',
                    data: historico.map(h => {
                        const ben = parseFloat(h.total_beneficios);
                        const ifd = parseFloat(h.total_ifood);
                        return (ben + ifd).toFixed(2);
                    }),
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                }]
            },
            options: {
                plugins: {
                    title: { display: true, text: 'Custo Total por Mês (VT + VR)' },
                    legend: { display: false }
                },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Benefícios x iFood
        new Chart(document.getElementById('chartBeneficiosIfood').getContext('2d'), {
            type: 'line',
            data: {
                labels: meses,
                datasets: [
                    {
                        label: 'Total VT (R$)',
                        data: totalBeneficios,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.2)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Total VR (R$)',
                        data: totalIfood,
                        borderColor: 'rgb(249, 115, 22)',
                        backgroundColor: 'rgba(249, 115, 22, 0.2)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: { responsive: true }
        });

        // Dias trabalhados x úteis
        new Chart(document.getElementById('chartDiasTrabalhados').getContext('2d'), {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [
                    { label: 'Dias Trabalhados', data: diasTrabalhados, backgroundColor: 'rgba(99, 102, 241, 0.7)' },
                    { label: 'Dias Úteis', data: diasUteis, backgroundColor: 'rgba(239, 68, 68, 0.7)' }
                ]
            },
            options: { responsive: true }
        });
    </script>
</x-app-layout>
