<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $employee->full_name }} — Detalhes
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto space-y-6">
        <div class="bg-white shadow rounded-lg p-6">

            {{-- 🧍 Informações do Funcionário --}}
            <div class="mb-8 bg-indigo-50 border border-indigo-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $employee->full_name }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-700">
                    <p><strong>Empresa:</strong> {{ $employee->company->name ?? 'Não Informado' }}</p>
                    <p><strong>Cargo:</strong> {{ $employee->position ?? 'Não Informado' }}</p>
                    <p><strong>Departamento:</strong> {{ $employee->department ?? 'Não Informado' }}</p>
                    <p><strong>Data de Nascimento:</strong>
                        {{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->format('d/m/Y') : 'Não Informado' }}
                    </p>
                    <p><strong>Nome da Mãe:</strong> {{ $employee->mother_name ?? 'Não Informado' }}</p>
                    <p><strong>CPF:</strong> {{ $employee->cpf ?? 'Não Informado' }}</p>
                    <p><strong>Código Solides:</strong> {{ $employee->cod_solides ?? 'Não Informado' }}</p>
                    <p><strong>Código VR:</strong> {{ $employee->cod_vr ?? 'Não Informado' }}</p>
                    <p>
                        <strong>Status:</strong>
                        <span class="{{ $employee->active ? 'text-green-600' : 'text-red-600' }}">
                            {{ $employee->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- 📊 Cards Estatísticos --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Presença Média</h4>
                    <p class="text-2xl font-semibold text-indigo-600">{{ $presencaMedia ?? 0 }}%</p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Ranking na Empresa</h4>
                    <p class="text-2xl font-semibold text-green-600">
                        #{{ $posicaoRanking ?? '-' }} <span class="text-gray-500 text-sm">de {{ $totalFuncionariosEmpresa }}</span>
                    </p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Total Acumulado de Benefícios</h4>
                    <p class="text-2xl font-semibold text-emerald-600">
                        R$ {{ number_format(optional($beneficiosAcumulados->last())->acumulado ?? 0, 2, ',', '.') }}
                    </p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Média de dias trabalhados</h4>
                    <p class="text-2xl font-semibold text-indigo-600">{{ number_format($mediaDiasTrabalhados, 1, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Média de faltas por mês</h4>
                    <p class="text-2xl font-semibold text-rose-600">{{ number_format($mediaFaltas, 1, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Média de benefícios</h4>
                    <p class="text-2xl font-semibold text-emerald-600">R$ {{ number_format($mediaBeneficios, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Média de iFood</h4>
                    <p class="text-2xl font-semibold text-orange-500">R$ {{ number_format($mediaIfood, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Total iFood</h4>
                    <p class="text-2xl font-semibold text-orange-600">R$ {{ number_format($totalIfood, 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- 💼 Benefícios Utilizados --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Benefícios Utilizados</h3>
            <table class="min-w-full border mb-6">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="px-3 py-2 border">Descrição</th>
                        <th class="px-3 py-2 border">Operadora</th>
                        <th class="px-3 py-2 border">Valor</th>
                        <th class="px-3 py-2 border">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($beneficiosUsados as $b)
                        <tr>
                            <td class="border px-3 py-2">{{ $b->description }}</td>
                            <td class="border px-3 py-2">{{ $b->operator ?? '-' }}</td>
                            <td class="border px-3 py-2">R$ {{ number_format($b->value, 2, ',', '.') }}</td>
                            <td class="border px-3 py-2">{{ $b->qtd }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- 📅 Histórico Mensal --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Histórico Mensal</h3>
            <table class="min-w-full border mb-8">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="px-3 py-2 border">Mês</th>
                        <th class="px-3 py-2 border">Dias Uteis</th>
                        <th class="px-3 py-2 border">Dias Trabalhados</th>
                        <th class="px-3 py-2 border">Total Benefícios</th>
                        <th class="px-3 py-2 border">Total iFood</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historico as $mes)
                        <tr>
                            <td class="border px-3 py-2">{{ $mes->mes }}</td>
                            <td class="border px-3 py-2">{{ number_format($mes->dias_uteis, 0, ',', '.') }}</td>
                            <td class="border px-3 py-2">{{ number_format($mes->dias_trabalhados, 0, ',', '.') }}</td>
                            <td class="border px-3 py-2">R$ {{ number_format($mes->total_beneficios, 2, ',', '.') }}</td>
                            <td class="border px-3 py-2">R$ {{ number_format($mes->total_ifood, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- 📊 Gráficos Analíticos --}}
            <h3 class="text-lg font-semibold text-gray-700 mt-10 mb-3">Análises e Gráficos</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Gráfico 1: Dias Trabalhados vs Úteis --}}
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartDiasTrabalhados" height="150"></canvas>
                </div>

                {{-- Gráfico 2: Distribuição de Benefícios --}}
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartDistribuicaoBeneficios" height="150"></canvas>
                </div>

                {{-- Gráfico 3: Benefícios x iFood --}}
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartBeneficiosIfood" height="150"></canvas>
                </div>


                {{-- Gráfico 4: Evolução Acumulada --}}
                <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartBeneficioAcumulado" height="150"></canvas>
                </div>
            </div>

            {{-- 📄 Botões de ação --}}
            <div class="mt-8 flex justify-between">
                <a href="{{ route('employees.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                    Voltar
                </a>

                {{-- <a href="{{ route('employees.report.pdf', $employee->id) }}" target="_blank"
                   class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                    Gerar PDF do Relatório
                </a> --}}
            </div>
        </div>
    </div>

    {{-- ==================== CHARTS ==================== --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const historico = @json($historico);
            const beneficios = @json($beneficiosUsados);
            const acumulado = @json($beneficiosAcumulados);

            const meses = historico.map(item => item.mes);
            const totalBeneficios = historico.map(item => item.total_beneficios ?? 0);
            const totalIfood = historico.map(item => item.total_ifood ?? 0);
            const diasTrabalhados = historico.map(item => item.dias_trabalhados ?? 0);
            const diasUteis = historico.map(item => item.dias_uteis ?? 0);

            // === Gráfico 1: Benefícios x iFood ===
            new Chart(document.getElementById('chartBeneficiosIfood').getContext('2d'), {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [
                        {
                            label: 'Total Benefícios (R$)',
                            data: totalBeneficios,
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.2)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Total iFood (R$)',
                            data: totalIfood,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.2)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Evolução de Gastos Mensais' }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // === Gráfico 2: Distribuição de Benefícios ===
            new Chart(document.getElementById('chartDistribuicaoBeneficios').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: beneficios.map(b => b.description),
                    datasets: [{
                        label: 'Valor (R$)',
                        data: beneficios.map(b => b.value),
                        backgroundColor: [
                            '#4F46E5', '#22C55E', '#F59E0B', '#EF4444', '#06B6D4',
                            '#8B5CF6', '#EC4899', '#10B981', '#F97316', '#6366F1'
                        ],
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'right' },
                        title: { display: true, text: 'Distribuição dos Benefícios Atuais' }
                    }
                }
            });

            // === Gráfico 3: Dias Trabalhados vs Dias Úteis ===
            new Chart(document.getElementById('chartDiasTrabalhados').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: meses,
                    datasets: [
                        {
                            label: 'Dias Trabalhados',
                            data: diasTrabalhados,
                            backgroundColor: 'rgba(99, 102, 241, 0.6)',
                        },
                        {
                            label: 'Dias Úteis',
                            data: diasUteis,
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Comparativo de Dias Trabalhados x Úteis' }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // === Gráfico 4: Evolução Acumulada ===
            const mesesAcumulado = acumulado.map(item => item.mes);
            const valoresAcumulados = acumulado.map(item => item.acumulado);
            new Chart(document.getElementById('chartBeneficioAcumulado').getContext('2d'), {
                type: 'line',
                data: {
                    labels: mesesAcumulado,
                    datasets: [{
                        label: 'Benefícios Acumulados (R$)',
                        data: valoresAcumulados,
                        borderColor: 'rgb(234, 88, 12)',
                        backgroundColor: 'rgba(234, 88, 12, 0.2)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Evolução Acumulada de Benefícios' }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
    </script>
</x-app-layout>
