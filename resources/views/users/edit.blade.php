<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Editar Usuário</h2></x-slot>
    <div class="py-6 max-w-4xl mx-auto">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf @method('PUT')
                @include('users._form', ['user' => $user])
                <div class="mt-4 text-right">
                    <a href="{{ route('users.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancelar</a>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
