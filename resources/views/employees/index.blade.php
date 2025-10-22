<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Funcionários</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <form method="GET" action="{{ route('employees.index') }}" class="flex w-full sm:w-1/2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Buscar Funcionários..."
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
                        <th class="px-3 py-2 border">Nome</th>
                        <th class="px-3 py-2 border">Empresa</th>
                        <th class="px-3 py-2 border">Cargo</th>
                        <th class="px-3 py-2 border">Ativo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        <tr>
                            <td class="border px-3 py-2">{{ $employee->id }}</td>
                            <td class="border px-3 py-2">{{ $employee->full_name }}</td>
                            <td class="border px-3 py-2">{{ $employee->company->name ?? '-' }}</td>
                            <td class="border px-3 py-2">{{ $employee->position }}</td>
                            <td class="border px-3 py-2">{{ $employee->active ? 'Sim' : 'Não' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $employees->links() }}</div>
        </div>
    </div>
</x-app-layout>
