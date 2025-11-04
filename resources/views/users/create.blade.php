<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Novo Usuário</h2></x-slot>
    <div class="py-6 max-w-4xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <form method="POST" action="{{ route('users.store') }}">
                @include('users._form')
                <div class="mt-4 text-right">
                    <a href="{{ route('users.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancelar</a>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
