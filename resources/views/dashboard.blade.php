<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard — {{ $refMes }}
            </h2>

            <form method="GET" action="{{ route('dashboard') }}" @submit="loading = true" class="flex items-center gap-2">
                <input type="hidden" name="y" value="{{ now()->year }}">
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
            </form>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <x-dashboard-card title="Funcionários c/ divergência" :value="$funcsDiasDiferentes" />
                <x-dashboard-card title="Total Benefícios" :value="number_format($totalBeneficios, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card title="Total iFood" :value="number_format($totalIfood, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card title="Média benefício/func." :value="number_format($avgBeneficioPorFuncionario, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card title="Média iFood/func." :value="number_format($avgIfoodPorFuncionario, 2, ',', '.')" prefix="R$" />
                <x-dashboard-card title="Média passagens/func." :value="number_format($avgPassagensPorFuncionario, 2, ',', '.')" />
                <x-dashboard-card title="Funcionários (total)" :value="$totalFuncionarios" />
                <x-dashboard-card title="Inativos" :value="$totalInativos" />
            </div>

            {{-- Gráfico de barras --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Benefícios Mais Utilizados — {{ $refMes }}</h3>
                <canvas id="beneficiosChart" height="120"></canvas>
            </div>
        </div>
    </div>

    {{-- Chart.js --}}
    <script>
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
    </script>
</x-app-layout>
