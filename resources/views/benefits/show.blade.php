<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $benefit->name }} — Análise Completa do Benefício
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto space-y-6">
        <div class="bg-white shadow rounded-lg p-6">

            {{-- 🧾 Cards resumo --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
                <x-stat-card label="Total (período)" :value="'R$ '.number_format($totalBeneficioPeriodo, 2, ',', '.')" color="emerald" />
                <x-stat-card label="Média/Funcionário" :value="'R$ '.number_format($mediaPorFuncionario, 2, ',', '.')" color="indigo" />
                {{-- <x-stat-card label="Funcionários (últ. mês)" :value="$funcAtivosUltimoMes" color="blue" /> --}}
                <x-stat-card label="Custo Médio/Dia" :value="'R$ '.number_format($custoMedioPorDia, 2, ',', '.')" color="orange" />
                {{-- <x-stat-card label="Total iFood (período)" :value="'R$ '.number_format($totalIfoodPeriodo, 2, ',', '.')" color="orange" /> --}}
                <x-stat-card label="% no Total de Benefícios" :value="$participacaoNoTotal.'%'" color="rose" />
            </div>

                        {{-- 📅 Histórico Mensal (DESC na tabela) --}}
            <h3 class="text-lg font-semibold text-gray-700 mt-10 mb-3">Histórico Mensal</h3>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full border">
                    <thead>
                        <tr class="bg-gray-100 text-left">
                            <th class="px-3 py-2 border">Mês</th>
                            <th class="px-3 py-2 border">Total Benefício</th>
                            <th class="px-3 py-2 border">Média / Funcionário</th>
                            <th class="px-3 py-2 border">Funcionários</th>
                            <th class="px-3 py-2 border">Total iFood</th>
                            <th class="px-3 py-2 border">Outros Benefícios</th>
                            <th class="px-3 py-2 border">Total Geral</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historicoTabela as $linha)
                            <tr>
                                <td class="border px-3 py-2">{{ $linha->mes }}</td>
                                <td class="border px-3 py-2">R$ {{ number_format($linha->total_beneficio, 2, ',', '.') }}</td>
                                <td class="border px-3 py-2">R$ {{ number_format($linha->media_func, 2, ',', '.') }}</td>
                                <td class="border px-3 py-2">{{ $linha->qtd_func }}</td>
                                <td class="border px-3 py-2">R$ {{ number_format($linha->total_ifood, 2, ',', '.') }}</td>
                                <td class="border px-3 py-2">R$ {{ number_format($linha->outros_beneficios, 2, ',', '.') }}</td>
                                <td class="border px-3 py-2">R$ {{ number_format($linha->total_beneficios, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

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

    {{-- ====== Chart.js ====== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const historico      = @json($historico);
        const topFuncionarios= @json($topFuncionarios);

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
                    { label: 'Benefício (R$)', data: serieBeneficio, borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.2)', tension: 0.3, fill: true },
                    { label: 'iFood (R$)',     data: serieIfood,     borderColor: '#F97316', backgroundColor: 'rgba(249,115,22,0.2)', tension: 0.3, fill: true },
                    { label: 'Outros (R$)',    data: serieOutros,    borderColor: '#94A3B8', backgroundColor: 'rgba(148,163,184,0.2)', tension: 0.3, fill: true },
                ]
            },
            options: {
                plugins: { title: { display: true, text: 'Evolução Mensal — Benefício x iFood x Outros' } },
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
                labels: ['Este Benefício', 'iFood', 'Outros Benefícios'],
                datasets: [{ data: [totalBeneficio, totalIfood, totalOutros], backgroundColor: ['#10B981', '#F97316', '#94A3B8'] }]
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
                datasets: [{ label: 'Média de Dias Trabalhados', data: serieDias, backgroundColor: 'rgba(59,130,246,0.75)' }]
            },
            options: {
                plugins: { title: { display: true, text: 'Média de Dias Trabalhados (somente beneficiários do mês)' } },
                scales: { y: { beginAtZero: true } }
            }
        });


        // 7) Top 10 Funcionários (barras horizontais)
        new Chart(document.getElementById('chartTopFuncionarios').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topFuncionarios.map(f => f.full_name.length > 20 ? f.full_name.substring(0, 20) + '…' : f.full_name),
                datasets: [{ label: 'Total Recebido (R$)', data: topFuncionarios.map(f => Number(f.total_recebido)||0), backgroundColor: 'rgba(99,102,241,0.85)' }]
            },
            options: {
                indexAxis: 'y',
                plugins: { title: { display: true, text: 'Top 10 Funcionários — Total Recebido no Período' } },
                scales: { x: { beginAtZero: true } }
            }
        });
    </script>
</x-app-layout>
