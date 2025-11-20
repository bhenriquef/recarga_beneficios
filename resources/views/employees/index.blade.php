<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Funcionários</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">

            {{-- 🔎 Barra de busca + filtros --}}
            <form
                method="GET"
                action="{{ route('employees.index') }}"
                class="space-y-3"
            >
                {{-- Linha 1: busca --}}
                <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                    <div class="flex w-full sm:w-2/3">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por nome, CPF, Solides, VR, departamento..."
                            class="w-full rounded-l-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <button
                            type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-r-md text-sm hover:bg-indigo-700 transition"
                        >
                            Buscar
                        </button>
                    </div>

                    <div class="flex-1 text-sm text-gray-500 sm:text-right">
                        {{ $employees->total() }} funcionário(s) encontrado(s)
                    </div>
                </div>

                {{-- Linha 2: filtros extras --}}
                <div class="flex flex-wrap gap-3 items-center text-sm">
                    {{-- Ativos/Inativos --}}
                    <div class="flex items-center gap-2">
                        <span class="text-gray-600 text-xs uppercase">Status</span>
                        <select
                            name="only_active"
                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="1" @selected(($onlyActive ?? '') === '1')>Somente ativos</option>
                            <option value="0" @selected(($onlyActive ?? '') === '0')>Somente inativos</option>
                        </select>
                    </div>

                    {{-- Somente com benefícios --}}
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input
                            type="checkbox"
                            name="only_with_benefits"
                            value="1"
                            @checked($onlyWithBenefits ?? false)
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span>Apenas com benefícios</span>
                    </label>

                    {{-- Somente com Solides --}}
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input
                            type="checkbox"
                            name="only_with_solides"
                            value="1"
                            @checked($onlyWithSolides ?? false)
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span>Apenas com código Solides</span>
                    </label>

                    {{-- Somente com VR --}}
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input
                            type="checkbox"
                            name="only_with_vr"
                            value="1"
                            @checked($onlyWithVr ?? false)
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span>Apenas com código VR</span>
                    </label>

                    {{-- Botão limpar --}}
                    <a
                        href="{{ route('employees.index') }}"
                        class="ml-auto text-xs text-gray-500 hover:text-gray-700 underline"
                    >
                        Limpar filtros
                    </a>
                </div>
            </form>

            {{-- 📋 Tabela de funcionários --}}
            <div class="mt-4">
                @if ($employees->isEmpty())
                    <p class="text-sm text-gray-500">
                        Nenhum funcionário encontrado com os filtros informados.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2 border">Nome</th>
                                    <th class="px-3 py-2 border">Empresa</th>
                                    <th class="px-3 py-2 border">Status</th>
                                    <th class="px-3 py-2 border">Cód. Solides</th>
                                    <th class="px-3 py-2 border">Cód. VR</th>
                                    <th class="px-3 py-2 border text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($employees as $employee)
                                    <tr>
                                        <td class="px-3 py-2">
                                            {{ $employee->full_name }}
                                        </td>

                                        <td class="px-3 py-2">
                                            {{ $employee->company->name ?? '—' }}
                                        </td>

                                        <td class="px-3 py-2">
                                            @if($employee->active)
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
                                            {{ $employee->cod_solides ?: '—' }}
                                        </td>

                                        <td class="px-3 py-2">
                                            {{ $employee->cod_vr ?: '—' }}
                                        </td>

                                        <td class="px-3 py-2 text-center">
                                            <a
                                                href="{{ route('employees.show', $employee->id) }}"
                                                class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700"
                                            >
                                                Ver detalhes
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
