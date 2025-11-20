<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Empresas</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">

            {{-- 🔎 Barra de busca + filtros --}}
            <form
                method="GET"
                action="{{ route('companies.index') }}"
                class="space-y-3"
            >
                {{-- Linha 1: busca --}}
                <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                    <div class="flex w-full sm:w-2/3">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por nome, CNPJ, cidade, estado..."
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
                        {{ $companies->total() }} empresa(s) encontrada(s)
                    </div>
                </div>

                {{-- Linha 2: filtros extras --}}
                <div class="flex flex-wrap gap-3 items-center text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-600 text-xs uppercase">Funcionários</span>
                        <select
                            name="has_employees"
                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Todas</option>
                            <option value="1" @selected(($hasEmployees ?? '') === '1')>Somente com funcionários</option>
                            <option value="0" @selected(($hasEmployees ?? '') === '0')>Somente sem funcionários</option>
                        </select>
                    </div>

                    <a
                        href="{{ route('companies.index') }}"
                        class="ml-auto text-xs text-gray-500 hover:text-gray-700 underline"
                    >
                        Limpar filtros
                    </a>
                </div>
            </form>

            {{-- 📋 Tabela de empresas --}}
            <div class="mt-4">
                @if ($companies->isEmpty())
                    <p class="text-sm text-gray-500">
                        Nenhuma empresa encontrada com os filtros informados.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border">
                            <thead>
                                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2 border">Nome</th>
                                    <th class="px-3 py-2 border">CNPJ</th>
                                    <th class="px-3 py-2 border">Funcionários</th>
                                    <th class="px-3 py-2 border">Cidade</th>
                                    <th class="px-3 py-2 border">Estado</th>
                                    <th class="px-3 py-2 border text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($companies as $company)
                                    <tr>
                                        <td class="px-3 py-2">
                                            {{ $company->name }}
                                        </td>

                                        <td class="px-3 py-2">
                                            {{ $company->cnpj ?: '—' }}
                                        </td>

                                        <td class="px-3 py-2">
                                            @if($company->employees_count > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                    {{ $company->employees_count }} funcionário(s)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                    Nenhum funcionário
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-2">
                                            {{ $company->city ?: '—' }}
                                        </td>

                                        <td class="px-3 py-2">
                                            {{ $company->state ?: '—' }}
                                        </td>

                                        <td class="px-3 py-2 text-center">
                                            <a
                                                href="{{ route('companies.show', $company->id) }}"
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
                        {{ $companies->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
