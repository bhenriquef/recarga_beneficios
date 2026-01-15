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
                <p><strong>CPF:</strong> {{ $employee->cpf ?? 'Não Informado' }}</p>
                <p><strong>Código Solides:</strong> {{ $employee->cod_solides ?? 'Não Informado' }}</p>

                <p>
                    <strong>Status:</strong>
                    <span class="{{ $employee->active ? 'text-green-600' : 'text-red-600' }}">
                        {{ $employee->active ? 'Ativo' : 'Inativo' }}
                    </span>
                </p>
                <p><strong>Data de Demissão:</strong>
                    {{ $employee->shutdown_date ? \Carbon\Carbon::parse($employee->shutdown_date)->format('d/m/Y') : ($employee->active ? 'Usuario não demitido' : 'Não Informado') }}
                </p>
                <p>
                    <strong>Tempo de empresa:</strong>
                    {{ $tempoEmpresaTexto ?? 'Não informado' }}
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

                <form method="GET" action="{{ route('employees.show', $employee->id) }}" class="flex flex-wrap items-center gap-2">
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
                    <h4 class="text-sm text-gray-500">Total Vale Transporte</h4>
                    <p class="text-2xl font-semibold text-emerald-600">
                        R$ {{ number_format($totalBeneficios, 1, ',', '.') }}
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
                    <h4 class="text-sm text-gray-500">Média de Vale Transporte por mês</h4>
                    <p class="text-2xl font-semibold text-emerald-600">R$ {{ number_format($mediaBeneficios, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Média de Vale Refeição por mês</h4>
                    <p class="text-2xl font-semibold text-orange-500">R$ {{ number_format($mediaIfood, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <h4 class="text-sm text-gray-500">Total Vale Refeição</h4>
                    <p class="text-2xl font-semibold text-orange-600">R$ {{ number_format($totalIfood, 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- 💼 Benefícios Utilizados --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Beneficios Utilizados</h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Beneficios configurados para o funcionário
                    </p>

                    @if($beneficiosUsados->isNotEmpty())
                        <input
                            id="filter-beneficios"
                            type="text"
                            placeholder="Filtrar por descrição, operadora..."
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    @endif
                </div>

                @if($beneficiosUsados->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum beneficio configurado para este funcionário.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-beneficios" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Descrição</th>
                                    <th class="px-3 py-2">Operadora</th>
                                    <th class="px-3 py-2">Valor</th>
                                    <th class="px-3 py-2">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($beneficiosUsados as $b)
                                    <tr>
                                        <td class="px-3 py-2">{{ $b->description }}</td>
                                        <td class="px-3 py-2">{{ $b->operator ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($b->value, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">{{ $b->qtd }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <span id="beneficios-info"></span>
                        <div id="beneficios-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>

            <h3 class="text-lg font-semibold text-gray-700 mb-3">Perda estimada na demissão</h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                @if(is_null($perdaDemissao))
                    <p class="text-sm text-gray-500">
                        Nenhuma perda estimada para este funcionário (sem data de demissão ou sem recargas no mês de referência).
                    </p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase">Mês de referência</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $perdaDemissao->mes_referencia_label }}</p>
                            <p class="text-sm text-gray-600 mt-1">
                                Período: {{ $perdaDemissao->periodo_inicio }} a {{ $perdaDemissao->periodo_fim }}
                            </p>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase">Demissão</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $perdaDemissao->shutdown_date }}</p>
                            <p class="text-sm text-gray-600 mt-1">
                                Dias úteis restantes (Seg–Sáb): <strong>{{ $perdaDemissao->dias_uteis_restantes }}</strong>
                            </p>
                        </div>

                        <div class="bg-rose-50 border border-rose-200 rounded-lg p-4">
                            <p class="text-xs text-rose-700 uppercase">Perda estimada</p>
                            <p class="text-2xl font-semibold text-rose-700">
                                R$ {{ number_format($perdaDemissao->perda_estimada, 2, ',', '.') }}
                            </p>
                            <p class="text-sm text-rose-700 mt-1">
                                Base: R$ {{ number_format($perdaDemissao->value_base, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    <div class="text-sm text-gray-700 space-y-2 mb-4">
                        <p>
                            <strong>Como calculamos:</strong>
                            Consideramos o total recarregado no mês (valor base) e distribuímos pelos dias úteis do mês (Seg–Sáb).
                            Depois multiplicamos pelos dias úteis restantes após a demissão.
                        </p>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm">
                            <p class="text-gray-700">
                                <strong>Fórmula:</strong>
                                perda estimada = (valor base ÷ dias uteis mes) × dias uteis restantes
                            </p>
                            <p class="text-gray-700 mt-1">
                                = (R$ {{ number_format($perdaDemissao->value_base, 2, ',', '.') }}
                                ÷ {{ $perdaDemissao->dias_uteis_mes }})
                                × {{ $perdaDemissao->dias_uteis_restantes }}
                                = <strong>R$ {{ number_format($perdaDemissao->perda_estimada, 2, ',', '.') }}</strong>
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <p class="text-xs text-gray-500 uppercase">Dias úteis no mês</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $perdaDemissao->dias_uteis_mes }}</p>
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <p class="text-xs text-gray-500 uppercase">Valor por dia útil</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    R$ {{ number_format($perdaDemissao->valor_por_dia, 2, ',', '.') }}
                                </p>
                            </div>
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <p class="text-xs text-gray-500 uppercase">Dias úteis restantes</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $perdaDemissao->dias_uteis_restantes }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        @if($perdaDemissaoBeneficios->isEmpty())
                            <p class="text-sm text-gray-500">Nenhum benefício mensal encontrado para compor o valor base.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                            <th class="px-3 py-2">Benefício</th>
                                            <th class="px-3 py-2">Operadora</th>
                                            <th class="px-3 py-2">Cod</th>
                                            {{-- <th class="px-3 py-2">Total</th>
                                            <th class="px-3 py-2">Final</th> --}}
                                            <th class="px-3 py-2">Valor usado no cálculo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($perdaDemissaoBeneficios as $row)
                                            <tr>
                                                <td class="px-3 py-2">{{ $row->description }}</td>
                                                <td class="px-3 py-2">{{ $row->operator ?? '-' }}</td>
                                                <td class="px-3 py-2">{{ $row->cod ?? '-' }}</td>
                                                {{-- <td class="px-3 py-2">R$ {{ number_format($row->total_value ?? 0, 2, ',', '.') }}</td>
                                                <td class="px-3 py-2">
                                                    {{ ($row->final_value !== null) ? 'R$ '.number_format($row->final_value, 2, ',', '.') : '-' }}
                                                </td> --}}
                                                <td class="px-3 py-2 font-semibold">
                                                    R$ {{ number_format($row->value_used ?? 0, 2, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            </div>


            {{-- 📅 Histórico Mensal --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Histórico Mensal (no período selecionado)</h3>

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
                    <p class="text-sm text-gray-500">Nenhum histórico encontrado no período selecionado.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-historico" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Mês</th>
                                    <th class="px-3 py-2">Dias Úteis</th>
                                    <th class="px-3 py-2">Dias Calculados</th>
                                    <th class="px-3 py-2">Vale Refeição</th>
                                    <th class="px-3 py-2">Mobilidade Ifood</th>
                                    <th class="px-3 py-2">Vale Transporte Ifood</th>
                                    <th class="px-3 py-2">VT Calculado</th>
                                    <th class="px-3 py-2">VT Acumulado</th>
                                    <th class="px-3 py-2">VT Economizado</th>
                                    <th class="px-3 py-2">VT Recarga</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($historicoTabela as $mes)
                                    <tr>
                                        <td class="px-3 py-2">{{ $mes->mes }}</td>
                                        <td class="px-3 py-2">{{ number_format($mes->dias_uteis, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2">{{ number_format($mes->dias_calculados, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($mes->total_vale_alimentacao, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($mes->total_mobilidade, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($mes->total_ifood, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($mes->total_beneficios, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($mes->total_acumulado, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($mes->total_economizado, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            R$ {{ number_format($mes->total_real, 2, ',', '.') }}
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

            {{-- 🚫 Faltas / Afastamentos no Período --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Faltas / Afastamentos no Período</h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Período: <strong>{{ $periodLabel }}</strong>
                    </p>

                    @if($faltasPeriodo->isNotEmpty())
                        <input
                            id="filter-faltas"
                            type="text"
                            placeholder="Filtrar por motivo..."
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    @endif
                </div>

                @if($faltasPeriodo->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma falta ou afastamento registrado no período.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-faltas" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Início</th>
                                    <th class="px-3 py-2">Fim</th>
                                    <th class="px-3 py-2">Dias</th>
                                    <th class="px-3 py-2">Motivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($faltasPeriodo as $f)
                                    @php
                                        $inicio = \Carbon\Carbon::parse($f->start_date);
                                        $fim    = \Carbon\Carbon::parse($f->end_date);
                                        $dias   = $inicio->diffInDays($fim) + 1;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">{{ $inicio->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">{{ $fim->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">{{ $dias }}</td>
                                        <td class="px-3 py-2">{{ $f->reason ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <span id="faltas-info"></span>
                        <div id="faltas-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>

            {{-- 🌴 Férias no Período e Futuras --}}
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Férias no Período e Futuras</h3>

            <div class="bg-white rounded-lg shadow p-4 mb-8">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-600">
                        Período: <strong>{{ $periodLabel }}</strong>
                    </p>

                    @if($feriasLista->isNotEmpty())
                        <input
                            id="filter-ferias"
                            type="text"
                            placeholder="Filtrar por categoria..."
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    @endif
                </div>

                @if($feriasLista->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma férias cadastrada no período ou futura.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="table-ferias" class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Categoria</th>
                                    <th class="px-3 py-2">Início</th>
                                    <th class="px-3 py-2">Fim</th>
                                    <th class="px-3 py-2">Dias</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($feriasLista as $fer)
                                    @php
                                        $inicio = \Carbon\Carbon::parse($fer->start_date);
                                        $fim    = \Carbon\Carbon::parse($fer->end_date);
                                        $dias   = $inicio->diffInDays($fim) + 1;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">
                                            @if($fer->categoria === 'Futura')
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700">
                                                    Futura
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                    No período
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ $inicio->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">{{ $fim->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">{{ $dias }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <span id="ferias-info"></span>
                        <div id="ferias-pagination" class="flex flex-wrap gap-1 justify-end"></div>
                    </div>
                @endif
            </div>



            {{-- 📊 Gráficos Analíticos --}}
            <h3 class="text-lg font-semibold text-gray-700 mt-10 mb-3">Análises e Gráficos</h3>
            <div class="text-sm text-gray-600 mb-4">
                Período: <strong>{{ $periodLabel }}</strong>
                <span class="text-gray-400">
                    ({{ \Carbon\Carbon::parse($startDateStr)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($endDateStr)->format('d/m/Y') }})
                </span>
            </div>
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
                {{-- <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartBeneficiosIfood" height="150"></canvas>
                </div> --}}


                {{-- Gráfico 4: Evolução Acumulada --}}
                {{-- <div class="bg-white p-4 rounded-lg shadow">
                    <canvas id="chartBeneficioAcumulado" height="150"></canvas>
                </div> --}}
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
            // Benefícios configurados
            initTableWithFilter({
                tableId: 'table-beneficios',
                filterInputId: 'filter-beneficios',
                infoId: 'beneficios-info',
                paginationId: 'beneficios-pagination',
                pageSize: 10,
            });

            // Histórico mensal
            initTableWithFilter({
                tableId: 'table-historico',
                filterInputId: 'filter-historico',
                infoId: 'historico-info',
                paginationId: 'historico-pagination',
                pageSize: 12,
            });

            // Faltas no período
            initTableWithFilter({
                tableId: 'table-faltas',
                filterInputId: 'filter-faltas',
                infoId: 'faltas-info',
                paginationId: 'faltas-pagination',
                pageSize: 10,
            });

            // Férias no período + futuras
            initTableWithFilter({
                tableId: 'table-ferias',
                filterInputId: 'filter-ferias',
                infoId: 'ferias-info',
                paginationId: 'ferias-pagination',
                pageSize: 10,
            });
        });

    </script>


    {{-- ==================== CHARTS ==================== --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const historico = @json($historico);
            const beneficios = @json($beneficiosUsados);
            const acumulado = @json($beneficiosAcumulados);

            const meses = historico.map(item => item.mes);
            const totalBeneficios = historico.map(item => item.total_beneficios ?? 0);
            const totalIfood = historico.map(item => item.total_ifood ?? 0);
            const diasTrabalhados = historico.map(item => item.dias_calculados ?? 0);
            const diasUteis = historico.map(item => item.dias_uteis ?? 0);

            // === Gráfico 1: Benefícios x iFood ===
            // new Chart(document.getElementById('chartBeneficiosIfood').getContext('2d'), {
            //     type: 'line',
            //     data: {
            //         labels: meses,
            //         datasets: [
            //             {
            //                 label: 'Total Vale Transporte (R$)',
            //                 data: totalBeneficios,
            //                 borderColor: 'rgb(99, 102, 241)',
            //                 backgroundColor: 'rgba(99, 102, 241, 0.2)',
            //                 tension: 0.3,
            //                 fill: true
            //             },
            //             {
            //                 label: 'Total VR (R$)',
            //                 data: totalIfood,
            //                 borderColor: 'rgb(34, 197, 94)',
            //                 backgroundColor: 'rgba(34, 197, 94, 0.2)',
            //                 tension: 0.3,
            //                 fill: true
            //             }
            //         ]
            //     },
            //     options: {
            //         responsive: true,
            //         plugins: {
            //             legend: { position: 'top' },
            //             title: { display: true, text: 'Evolução de Gastos Mensais' }
            //         },
            //         scales: { y: { beginAtZero: true } }
            //     }
            // });

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
            // new Chart(document.getElementById('chartBeneficioAcumulado').getContext('2d'), {
            //     type: 'line',
            //     data: {
            //         labels: mesesAcumulado,
            //         datasets: [{
            //             label: 'Vale Transporte Acumulado (R$)',
            //             data: valoresAcumulados,
            //             borderColor: 'rgb(234, 88, 12)',
            //             backgroundColor: 'rgba(234, 88, 12, 0.2)',
            //             fill: true,
            //             tension: 0.3
            //         }]
            //     },
            //     options: {
            //         responsive: true,
            //         plugins: {
            //             legend: { position: 'top' },
            //             title: { display: true, text: 'Evolução Acumulada de Vale Transporte' }
            //         },
            //         scales: { y: { beginAtZero: true } }
            //     }
            // });
        });
    </script>
</x-app-layout>
