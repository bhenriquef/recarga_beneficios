<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $benefit->description }} — Análise Completa do VT
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto space-y-6">
        <div class="bg-white shadow rounded-lg p-6">

            {{-- 📌 Informações do Benefício --}}
            <div class="mb-8 bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    {{ $benefit->description }}
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-700">
                    <p>
                        <strong>Código:</strong>
                        {{ $benefit->cod ?? 'Não informado' }}
                    </p>

                    <p>
                        <strong>Operadora:</strong>
                        {{ $benefit->operator ?? 'Não informado' }}
                    </p>

                    <p>
                        <strong>Tipo:</strong>
                        {{ $benefit->type ?? 'Não informado' }}
                    </p>

                    <p>
                        <strong>Região:</strong>
                        {{ $benefit->region ?? '—' }}
                    </p>

                    <p>
                        <strong>Valor base:</strong>
                        @if(!is_null($benefit->value))
                            R$ {{ number_format($benefit->value, 2, ',', '.') }}
                        @else
                            Não informado
                        @endif
                    </p>

                    <p>
                        <strong>Variável?</strong>
                        <span class="{{ $benefit->variable ? 'text-emerald-700' : 'text-gray-600' }}">
                            {{ $benefit->variable ? 'Sim' : 'Não' }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- 🔎 Filtro de Período --}}
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <p class="text-sm text-gray-600">
                    Período analisado:
                    <strong>{{ $periodLabel }}</strong>
                    <span class="text-gray-400">
                        (de {{ \Carbon\Carbon::parse($startDateStr)->format('d/m/Y') }}
                        a {{ \Carbon\Carbon::parse($endDateStr)->format('d/m/Y') }})
                    </span>
                </p>

                <form method="GET" action="{{ route('benefits.show', $benefit->id) }}" class="flex flex-wrap items-center gap-2">
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

            {{-- 🧾 Cards resumo --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <x-stat-card
                    label="Total (período)"
                    :value="'R$ '.number_format($totalBeneficioPeriodo, 2, ',', '.')"
                    color="emerald"
                />
                <x-stat-card
                    label="Média/Funcionário"
                    :value="'R$ '.number_format($mediaPorFuncionario, 2, ',', '.')"
                    color="indigo"
                />
                <x-stat-card
                    label="Custo Médio/Dia"
                    :value="'R$ '.number_format($custoMedioPorDia, 2, ',', '.')"
                    color="orange"
                />
                <x-stat-card
                    label="% no Total de VT"
                    :value="$participacaoNoTotal.'%'"
                    color="rose"
                />
            </div>

            {{-- 📅 Histórico Mensal (com filtro + paginação) --}}
            <h3 class="text-lg font-semibold text-gray-700 mt-10 mb-3">Histórico Mensal</h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Evolução mensal do VT no período selecionado.
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
                    <p class="text-sm text-gray-500">
                        Nenhum histórico encontrado para este VT no período.
                    </p>
                @else
                    <div class="overflow-x-auto mb-3">
                        <table id="table-historico" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Mês</th>
                                    <th class="px-3 py-2">Total VT</th>
                                    <th class="px-3 py-2">Média / Funcionário</th>
                                    <th class="px-3 py-2">Funcionários</th>
                                    <th class="px-3 py-2">Total VR</th>
                                    <th class="px-3 py-2">Outros VT</th>
                                    <th class="px-3 py-2">Total Geral</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($historicoTabela as $linha)
                                    <tr>
                                        <td class="px-3 py-2">{{ $linha->mes }}</td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($linha->total_beneficio, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($linha->media_func, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">{{ $linha->qtd_func }}</td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($linha->total_ifood, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($linha->outros_beneficios, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($linha->total_beneficios, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
                        <span id="historico-info"></span>
                        <div id="historico-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>

            {{-- 👥 Funcionários que recebem este benefício --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">
                Funcionários que possuem este VT (no período)
            </h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Total acumulado para cada funcionário no período selecionado.
                    </p>

                    @if($funcionariosBeneficio->isNotEmpty())
                        <input
                            id="filter-func-benefit"
                            type="text"
                            placeholder="Filtrar por nome..."
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    @endif
                </div>

                @if($funcionariosBeneficio->isEmpty())
                    <p class="text-sm text-gray-500">
                        Nenhum funcionário recebeu este VT no período selecionado.
                    </p>
                @else
                    <div class="overflow-x-auto mb-3">
                        <table id="table-func-benefit" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Funcionário</th>
                                    <th class="px-3 py-2">Ativo?</th>
                                    <th class="px-3 py-2">Total no VT (R$)</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($funcionariosBeneficio as $f)
                                    <tr>
                                        <td class="px-3 py-2">
                                            {{ $f->full_name }}
                                        </td>

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

                                        <td class="px-3 py-2">
                                            R$ {{ number_format($f->total_beneficio, 2, ',', '.') }}
                                        </td>

                                        <td class="px-3 py-2 text-right">
                                            <a
                                                href="{{ route('employees.show', $f->id) }}"
                                                class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700"
                                            >
                                                Ver funcionário
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
                        <span id="func-benefit-info"></span>
                        <div id="func-benefit-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>

            {{-- 📊 Gráficos --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartEvolucao" height="160"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartMediaDias" height="160"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartDistribuicaoPeriodo" height="160"></canvas>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartTopFuncionarios" height="240"></canvas>
                </div>
            </div>

            {{-- Ação --}}
            <div class="mt-8">
                <a href="{{ route('benefits.index') }}"
                   class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                    Voltar
                </a>
            </div>
        </div>
    </div>

    {{-- ====== Filtro + Paginação de Tabela ====== --}}
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
            // Histórico mensal do benefício
            initTableWithFilter({
                tableId: 'table-historico',
                filterInputId: 'filter-historico',
                infoId: 'historico-info',
                paginationId: 'historico-pagination',
                pageSize: 12,
            });

            // Funcionários que possuem este benefício
            initTableWithFilter({
                tableId: 'table-func-benefit',
                filterInputId: 'filter-func-benefit',
                infoId: 'func-benefit-info',
                paginationId: 'func-benefit-pagination',
                pageSize: 10,
            });
        });
    </script>

    {{-- ====== Chart.js ====== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const historico       = @json($historico);
        const topFuncionarios = @json($topFuncionarios);

        const meses           = historico.map(h => h.mes);
        const serieBeneficio  = historico.map(h => Number(h.total_beneficio)   || 0);
        const serieIfood      = historico.map(h => Number(h.total_ifood)       || 0);
        const serieOutros     = historico.map(h => Number(h.outros_beneficios) || 0);
        const serieDias       = historico.map(h => Number(h.media_dias)        || 0);
        const serieCustoDia   = historico.map(h => Number(h.custo_por_dia)     || 0);

        // 1) Evolução: Benefício x iFood x Outros
        new Chart(document.getElementById('chartEvolucao').getContext('2d'), {
            type: 'line',
            data: {
                labels: meses,
                datasets: [
                    {
                        label: 'Benefício (R$)',
                        data: serieBeneficio,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16,185,129,0.2)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'iFood (R$)',
                        data: serieIfood,
                        borderColor: '#F97316',
                        backgroundColor: 'rgba(249,115,22,0.2)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Outros (R$)',
                        data: serieOutros,
                        borderColor: '#94A3B8',
                        backgroundColor: 'rgba(148,163,184,0.2)',
                        tension: 0.3,
                        fill: true
                    },
                ]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolução Mensal — VT x VR x Outros'
                    }
                },
                scales: { y: { beginAtZero: true } }
            }
        });

        // 2) Pizza: Distribuição no período (Benefício x iFood x Outros)
        const totalBeneficio = serieBeneficio.reduce((a,b)=>a+b,0);
        const totalIfood     = serieIfood.reduce((a,b)=>a+b,0);
        const totalOutros    = serieOutros.reduce((a,b)=>a+b,0);

        new Chart(document.getElementById('chartDistribuicaoPeriodo').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Este VT', 'VR', 'Outros VR'],
                datasets: [{
                    data: [totalBeneficio, totalIfood, totalOutros],
                    backgroundColor: ['#10B981', '#F97316', '#94A3B8']
                }]
            },
            options: {
                plugins: {
                    title: { display: true, text: 'Distribuição de Custos no Período' },
                    legend: { position: 'bottom' }
                }
            }
        });

        // 3) Média de dias trabalhados (beneficiários)
        new Chart(document.getElementById('chartMediaDias').getContext('2d'), {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Média de Dias Trabalhados',
                    data: serieDias,
                    backgroundColor: 'rgba(59,130,246,0.75)'
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Média de Dias Trabalhados (somente beneficiários do mês)'
                    }
                },
                scales: { y: { beginAtZero: true } }
            }
        });

        // 4) Top 10 Funcionários (barras horizontais)
        new Chart(document.getElementById('chartTopFuncionarios').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topFuncionarios.map(f =>
                    f.full_name.length > 20 ? f.full_name.substring(0, 20) + '…' : f.full_name
                ),
                datasets: [{
                    label: 'Total Recebido (R$)',
                    data: topFuncionarios.map(f => Number(f.total_recebido) || 0),
                    backgroundColor: 'rgba(99,102,241,0.85)'
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Funcionários — Total Recebido no Período'
                    }
                },
                scales: { x: { beginAtZero: true } }
            }
        });
    </script>
</x-app-layout>
