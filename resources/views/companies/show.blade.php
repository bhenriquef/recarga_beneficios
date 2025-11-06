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

            {{-- 📊 Cards de Métricas --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
                <x-stat-card label="Funcionários Ativos" :value="$totalFuncionarios" color="indigo" />
                <x-stat-card label="Média de Presença" :value="$mediaPresencaGeral.'%'" color="green" />
                <x-stat-card label="Média de Faltas" :value="number_format($mediaFaltas, 1, ',', '.')" color="rose" />
                <x-stat-card label="Total Benefícios" :value="'R$ '.number_format($beneficios->total ?? 0, 2, ',', '.')" color="emerald" />
                <x-stat-card label="Média Benefícios" :value="'R$ '.number_format($beneficios->media ?? 0, 2, ',', '.')" color="emerald" />
                <x-stat-card label="Total iFood" :value="'R$ '.number_format($ifood->total ?? 0, 2, ',', '.')" color="orange" />
                <x-stat-card label="Média iFood" :value="'R$ '.number_format($ifood->media ?? 0, 2, ',', '.')" color="orange" />
                <x-stat-card label="Top Funcionário" :value="e($topFuncionario->full_name ?? '-')" color="blue" value-size="text-base" truncate="true"/>
            </div>

            {{-- 📅 Histórico Mensal --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Histórico Mensal</h3>
            <table class="min-w-full border mb-8">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-3 py-2 border">Mês</th>
                        <th class="px-3 py-2 border">Média Dias Úteis</th>
                        <th class="px-3 py-2 border">Média Dias Trabalhados</th>
                        <th class="px-3 py-2 border">Total Benefícios</th>
                        <th class="px-3 py-2 border">Total iFood</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historicoTabela as $h)
                        <tr>
                            <td class="border px-3 py-2">{{ $h->mes }}</td>
                            <td class="border px-3 py-2">{{ $h->media_dias_uteis }}</td>
                            <td class="border px-3 py-2">{{ $h->media_dias_trabalhados }}</td>
                            <td class="border px-3 py-2">R$ {{ number_format($h->total_beneficios, 2, ',', '.') }}</td>
                            <td class="border px-3 py-2">R$ {{ number_format($h->total_ifood, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

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
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mt-4">
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
                        text: 'Custo Total por Benefício (incluindo iFood)',
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
                    data: historico.map(h => (h.media_dias_trabalhados / h.media_dias_uteis * 100).toFixed(1)),
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
                    title: { display: true, text: 'Custo Total por Mês (Benefícios + iFood)' },
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
                        label: 'Total Benefícios (R$)',
                        data: totalBeneficios,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.2)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Total iFood (R$)',
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
