<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Empresas</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <form method="GET" action="{{ route('companies.index') }}" @submit="loading = true" class="flex w-full sm:w-1/2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Buscar empresa..."
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
                        <th class="px-3 py-2 border">CNPJ</th>
                        <th class="px-3 py-2 border">Cidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $company)
                        <tr>
                            <td class="border px-3 py-2">{{ $company->id }}</td>
                            <td class="border px-3 py-2">{{ $company->name }}</td>
                            <td class="border px-3 py-2">{{ $company->cnpj }}</td>
                            <td class="border px-3 py-2">{{ $company->city }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $companies->links() }}</div>
        </div>
    </div>
</x-app-layout>
