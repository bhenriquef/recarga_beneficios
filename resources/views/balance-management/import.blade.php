<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Importar Gestão de Saldo
            </h2>
            <div class="text-sm text-gray-500">Apenas administradores</div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 text-green-700 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Subir planilha</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Colunas esperadas (cabecalhos): Matricula (CPF), ID Beneficio, VLR. SOLICITADO, SLD. ACUMULADO, VLR ECONOMIA,
                    VLR. FINAL PEDIDO e Competencia (data). Os valores em moeda podem estar com virgula ou ponto.
                </p>

                <form action="{{ route('balance.import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo (.xlsx .xls .csv)</label>
                        <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="block w-full" />
                        @error('file')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                            Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
