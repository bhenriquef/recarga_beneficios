<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>

            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                {{-- Select de mês --}}
                <select
                    name="m"
                    class="border border-gray-300 rounded-md px-5 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    onchange="this.form.submit()"
                >
                    @foreach($meses as $valor => $nome)
                        <option value="{{ $valor }}" @selected($mesAtual == $valor)>
                            {{ ucfirst($nome) }}
                        </option>
                    @endforeach
                </select>

                {{-- Select de ano --}}
                <select
                    name="y"
                    class="border border-gray-300 rounded-md px-5 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    onchange="this.form.submit()"
                >
                    @foreach($anos as $ano)
                        <option value="{{ $ano }}" @selected($anoAtual == $ano)>
                            {{ $ano }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                {{-- <x-dashboard-card title="Funcionários c/ divergência" :value="$funcsDiasDiferentes" /> --}}
                <x-dashboard-card title="Total Vale Transporte Calculado" :value="number_format($totalBeneficios, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card title="Total Vale Transporte Economizado" :value="number_format($totalEconomizado, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card title="Total Recarga Vale Transporte" :value="number_format($totalReal, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card title="Total Vale Refeição" :value="number_format($totalValeAlimentacao, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card
                    title="Total Mobilidade iFood"
                    :value="number_format($totalMobilidadeIfood, 2, ',', '.')"
                    prefix="R$"
                />
                <x-dashboard-card title="Total VT Ifood" :value="number_format($totalTransporteIfood, 2, ',', '.')" prefix="R$" />
                {{-- <x-dashboard-card title="Média Vale Transporte/func." :value="number_format($avgBeneficioPorFuncionario, 2, ',', '.')" prefix="R$" /> --}}
                {{-- <x-dashboard-card title="Média Vale Refeição/func." :value="number_format($avgIfoodPorFuncionario, 2, ',', '.')" prefix="R$" /> --}}
                <x-dashboard-card title="Funcionários Ativos" :value="$totalFuncionarios-$totalInativos" />
                <x-dashboard-card
                    title="Funcionários demitidos no período"
                    :value="$totalDemitidosMes"
                />
                {{-- <x-dashboard-card title="Inativos" :value="$totalInativos" /> --}}
            </div>

            {{-- Gráfico de barras - Top Benefícios --}}
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Top 10 Vale Transporte Mais Utilizados
                </h3>
                <canvas id="beneficiosChart" height="120"></canvas>
            </div>

            {{-- Gráficos de presença por empresa --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        Top 10 Empresas com Maior Média de Presença (%)
                    </h3>
                    <canvas id="empresasPresencaMaisChart" height="140"></canvas>
                    <p class="text-xs text-gray-500 mt-2">
                        Clique em uma barra para abrir a tela da empresa.
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        Top 10 Empresas com Menor Média de Presença (%)
                    </h3>
                    <canvas id="empresasPresencaMenosChart" height="140"></canvas>
                    <p class="text-xs text-gray-500 mt-2">
                        Clique em uma barra para abrir a tela da empresa.
                    </p>
                </div>
            </div>

            {{-- Gastos por empresa --}}
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Gastos por empresa
                    </h3>

                    @if($gastosPorEmpresa->isNotEmpty())
                        <div class="flex items-center gap-2">
                            <input
                                id="filter-gastos-empresa"
                                type="text"
                                placeholder="Filtrar por empresa..."
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                    @endif
                </div>

                @if($gastosPorEmpresa->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum dado encontrado para o período selecionado.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-gastos-empresa" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-4 py-2">Empresa</th>
                                    <th class="px-4 py-2">Mobilidade iFood</th>
                                    <th class="px-4 py-2">VT iFood</th>
                                    <th class="px-4 py-2">Valor calculado</th>
                                    <th class="px-4 py-2">Valor economizado</th>
                                    <th class="px-4 py-2">Valor recarga</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($gastosPorEmpresa as $row)
                                    <tr>
                                        <td class="px-4 py-2">{{ $row->company_name }}</td>

                                        <td class="px-4 py-2">
                                            @if((int)$row->mobilidade_cnt > 0)
                                                R$ {{ number_format((float)$row->mobilidade_total, 2, ',', '.') }}
                                            @else
                                                <span class="text-gray-400 italic">Não informado</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-2">
                                            @if((int)$row->ifood_vt_cnt > 0)
                                                R$ {{ number_format((float)$row->ifood_vt_total, 2, ',', '.') }}
                                            @else
                                                <span class="text-gray-400 italic">Não informado</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-2">
                                            @if((int)$row->valor_calculado_cnt > 0)
                                                R$ {{ number_format((float)$row->valor_calculado, 2, ',', '.') }}
                                            @else
                                                <span class="text-gray-400 italic">Não informado</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-2">
                                            @if((int)$row->valor_economizado_cnt > 0)
                                                R$ {{ number_format((float)$row->valor_economizado, 2, ',', '.') }}
                                            @else
                                                <span class="text-gray-400 italic">Não informado</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-2">
                                            @if((int)$row->valor_recarga_cnt > 0)
                                                R$ {{ number_format((float)$row->valor_recarga, 2, ',', '.') }}
                                            @else
                                                <span class="text-gray-400 italic">Não informado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <span id="gastos-empresa-info"></span>
                        <div id="gastos-empresa-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>

            {{-- Funcionários demitidos com perdas --}}
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Funcionários demitidos com perdas
                    </h3>

                    @if($demitidosComPerda->isNotEmpty())
                        <div class="flex items-center gap-2">
                            <input
                                id="filter-demitidos-perda"
                                type="text"
                                placeholder="Filtrar por empresa ou funcionário..."
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                    @endif
                </div>

                @if($demitidosComPerda->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum funcionário demitido encontrado no período.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-demitidos-perda" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-4 py-2">Empresa</th>
                                    <th class="px-4 py-2">Funcionário</th>
                                    <th class="px-4 py-2">Data demissão</th>
                                    <th class="px-4 py-2">Dias úteis restantes (Seg–Sáb)</th>
                                    <th class="px-4 py-2">Valor base</th>
                                    <th class="px-4 py-2">Perda estimada</th>
                                    <th class="px-4 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($demitidosComPerda as $row)
                                    <tr>
                                        <td class="px-4 py-2">{{ $row->company_name }}</td>
                                        <td class="px-4 py-2">{{ $row->full_name }}</td>
                                        <td class="px-4 py-2">{{ $row->shutdown_date }}</td>
                                        <td class="px-4 py-2">{{ $row->dias_uteis_restantes }}</td>

                                        <td class="px-4 py-2">
                                            @if(!is_null($row->value_base))
                                                R$ {{ number_format($row->value_base, 2, ',', '.') }}
                                            @else
                                                <span class="text-gray-400 italic">Não informado</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-2">
                                            @if(!is_null($row->perda_estimada))
                                                R$ {{ number_format($row->perda_estimada, 2, ',', '.') }}
                                            @else
                                                <span class="text-gray-400 italic">Não informado</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-2 text-right">
                                            <a
                                                href="{{ route('employees.show', $row->id) }}"
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
                        <span id="demitidos-perda-info"></span>
                        <div id="demitidos-perda-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>


            {{-- Funcionários com gasto de benefício > limite --}}
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Funcionários com gasto de VT acima de
                        R$ {{ number_format($limiteBeneficioAlto, 2, ',', '.') }}
                    </h3>

                    @if($funcionariosBeneficioAlto->isNotEmpty())
                        <div class="flex items-center gap-2">
                            <input
                                id="filter-beneficio-alto"
                                type="text"
                                placeholder="Filtrar por empresa ou funcionário..."
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                    @endif
                </div>

                @if($funcionariosBeneficioAlto->isEmpty())
                    <p class="text-sm text-gray-500">
                        Nenhum funcionário com gasto acima de
                        R$ {{ number_format($limiteBeneficioAlto, 2, ',', '.') }} no período selecionado.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-beneficio-alto" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-4 py-2">Empresa</th>
                                    <th class="px-4 py-2">Funcionário</th>
                                    <th class="px-4 py-2">Total Benefícios (R$)</th>
                                    <th class="px-4 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($funcionariosBeneficioAlto as $func)
                                    <tr>
                                        <td class="px-4 py-2">{{ $func->company_name }}</td>
                                        <td class="px-4 py-2">{{ $func->full_name }}</td>
                                        <td class="px-4 py-2">
                                            {{ number_format($func->total_beneficios, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <a
                                                href="{{ route('employees.show', $func->id) }}" {{-- ajuste se a rota for outra --}}
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
                        <span id="beneficio-alto-info"></span>
                        <div id="beneficio-alto-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>

            {{-- Funcionários sem dados de vale transporte --}}
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Funcionários sem dados de vale transporte (Solides sem VR)
                    </h3>

                    @if($funcSemVR->isNotEmpty())
                        <div class="flex items-center gap-2">
                            <input
                                id="filter-sem-vr"
                                type="text"
                                placeholder="Filtrar por empresa ou funcionário..."
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                    @endif
                </div>

                @if($funcSemVR->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum funcionário encontrado nessa condição.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-sem-vr" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-4 py-2">Empresa</th>
                                    <th class="px-4 py-2">Funcionário</th>
                                    <th class="px-4 py-2">Cód. Solides</th>
                                    <th class="px-4 py-2">Cód. VR</th>
                                    <th class="px-4 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($funcSemVR as $func)
                                    <tr>
                                        <td class="px-4 py-2">{{ $func->company_name }}</td>
                                        <td class="px-4 py-2">{{ $func->full_name }}</td>
                                        <td class="px-4 py-2">{{ $func->cod_solides }}</td>
                                        <td class="px-4 py-2 text-gray-400 italic">
                                            {{ $func->cod_vr ?: '— sem cadastro' }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <a
                                                href="{{ route('employees.show', $func->id) }}" {{-- ajuste essa rota se for diferente --}}
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
                        <span id="sem-vr-info"></span>
                        <div id="sem-vr-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

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

                const maxNumericButtons = 5; // quantos números mostrar (sem contar « e »)

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

                // Botão anterior
                paginationEl.appendChild(
                    createBtn(
                        Math.max(1, currentPage - 1),
                        '«',
                        { disabled: currentPage === 1 }
                    )
                );

                // Cálculo da janela de páginas ao redor da página atual
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

                // Primeira página + reticências se necessário
                if (startPage > 1) {
                    paginationEl.appendChild(
                        createBtn(1, '1', { active: currentPage === 1 })
                    );
                    if (startPage > 2) {
                        paginationEl.appendChild(createEllipsis());
                    }
                }

                // Páginas da janela
                for (let i = startPage; i <= endPage; i++) {
                    paginationEl.appendChild(
                        createBtn(i, i.toString(), { active: currentPage === i })
                    );
                }

                // Reticências + última página se necessário
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

                // Botão próximo
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
            initTableWithFilter({
                tableId: 'table-sem-vr',
                filterInputId: 'filter-sem-vr',
                infoId: 'sem-vr-info',
                paginationId: 'sem-vr-pagination',
                pageSize: 10,
            });

            initTableWithFilter({
                tableId: 'table-beneficio-alto',
                filterInputId: 'filter-beneficio-alto',
                infoId: 'beneficio-alto-info',
                paginationId: 'beneficio-alto-pagination',
                pageSize: 10,
            });

            initTableWithFilter({
                tableId: 'table-gastos-empresa',
                filterInputId: 'filter-gastos-empresa',
                infoId: 'gastos-empresa-info',
                paginationId: 'gastos-empresa-pagination',
                pageSize: 10,
            });

            initTableWithFilter({
                tableId: 'table-demitidos-perda',
                filterInputId: 'filter-demitidos-perda',
                infoId: 'demitidos-perda-info',
                paginationId: 'demitidos-perda-pagination',
                pageSize: 10,
            });

        });
    </script>



    {{-- Chart.js --}}
    <script>
        // Gráfico Top Benefícios
        const ctx = document.getElementById('beneficiosChart').getContext('2d');
        const beneficiosChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($topBeneficios->pluck('description')) !!},
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: {!! json_encode($topBeneficios->pluck('total')) !!},
                    borderWidth: 1,
                    backgroundColor: '#4F46E5'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });

        // ---------- GRÁFICOS DE PRESENÇA POR EMPRESA ----------

        const companyShowBaseUrl = "{{ url('/companies') }}/";

        // Arrays completos
        const empresasMaisFullLabels   = {!! json_encode($topEmpresasPresencaMaior->pluck('company_name')) !!};
        const empresasMaisData         = {!! json_encode($topEmpresasPresencaMaior->pluck('avg_presence')) !!};
        const empresasMaisIds          = {!! json_encode($topEmpresasPresencaMaior->pluck('company_id')) !!};

        const empresasMenosFullLabels  = {!! json_encode($topEmpresasPresencaMenor->pluck('company_name')) !!};
        const empresasMenosData        = {!! json_encode($topEmpresasPresencaMenor->pluck('avg_presence')) !!};
        const empresasMenosIds         = {!! json_encode($topEmpresasPresencaMenor->pluck('company_id')) !!};

        function truncateLabel(label, maxLength = 18) {
            if (!label) return '';
            return label.length > maxLength
                ? label.substring(0, maxLength - 1) + '…'
                : label;
        }

        const empresasMaisShortLabels  = empresasMaisFullLabels.map(l => truncateLabel(l));
        const empresasMenosShortLabels = empresasMenosFullLabels.map(l => truncateLabel(l));

        function createEmpresaChart(canvasId, shortLabels, fullLabels, data, ids) {
            const ctx = document.getElementById(canvasId).getContext('2d');

            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: shortLabels,
                    datasets: [{
                        label: 'Média de Presença (%)',
                        data: data,
                        borderWidth: 1,
                        backgroundColor: '#10B981'
                    }]
                },
                options: {
                    responsive: true,
                    onClick: function(evt, elements) {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const companyId = ids[index];
                            if (companyId) {
                                window.location.href = companyShowBaseUrl + companyId;
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 45,
                            }
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('pt-BR') + ' %';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(items) {
                                    const index = items[0].dataIndex;
                                    return fullLabels[index] || '';
                                },
                                label: function(context) {
                                    return context.raw.toLocaleString('pt-BR', {
                                        minimumFractionDigits: 2
                                    }) + ' %';
                                }
                            }
                        }
                    }
                }
            });
        }

        createEmpresaChart(
            'empresasPresencaMaisChart',
            empresasMaisShortLabels,
            empresasMaisFullLabels,
            empresasMaisData,
            empresasMaisIds
        );

        createEmpresaChart(
            'empresasPresencaMenosChart',
            empresasMenosShortLabels,
            empresasMenosFullLabels,
            empresasMenosData,
            empresasMenosIds
        );
    </script>
</x-app-layout>
