<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Benefícios</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <form method="GET" action="{{ route('benefits.index') }}" @submit="loading = true" class="flex w-full sm:w-1/2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Buscar Benefícios..."
                        class="w-full rounded-l-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <button
                        type="submit"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700 transition"
                    >
                        Buscar
                    </button>
                </form>

                {{-- <a href="{{ route('companies.create') }}" class="ml-4 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                    + Nova Empresa
                </a> --}}
            </div>
            <table class="min-w-full border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-3 py-2 border">ID</th>
                        <th class="px-3 py-2 border">Código</th>
                        <th class="px-3 py-2 border">Descrição</th>
                        <th class="px-3 py-2 border">Tipo</th>
                        <th class="px-3 py-2 border">Valor</th>
                        <th class="px-3 py-2 border">Nº Funcionarios</th>
                        <th class="px-3 py-2 border text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($benefits as $benefit)
                        <tr>
                            <td class="border px-3 py-2">{{ $benefit->id }}</td>
                            <td class="border px-3 py-2">{{ $benefit->cod }}</td>
                            <td class="border px-3 py-2">{{ $benefit->description }}</td>
                            <td class="border px-3 py-2">{{ $benefit->type }}</td>
                            <td class="border px-3 py-2">R$ {{ number_format($benefit->value, 2, ',', '.') }}</td>
                            <td class="border px-3 py-2">{{ $benefit->employees_count  }}</td>
                            <td class="border px-3 py-2 text-center">
                                <a href="{{ route('benefits.show', $benefit->id) }}"
                                   class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700 transition">
                                   Detalhes
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $benefits->links() }}</div>
        </div>
    </div>
</x-app-layout>
