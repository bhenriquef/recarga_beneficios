<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Usuários</h2>
    </x-slot>

    <div
        class="py-6 max-w-6xl mx-auto"
        x-data="{
            showCreate: {{ $errors->any() && old('form_type') === 'create' ? 'true' : 'false' }},
            showEdit: {{ $errors->any() && old('form_type') === 'edit' ? 'true' : 'false' }},
            showDelete: false,
            editUser: {},
            deleteUser: {},
        }"
    >
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <form method="GET" action="{{ route('users.index') }}" @submit="loading = true" class="flex w-full sm:w-1/2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Buscar usuário..."
                        class="w-full rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700 transition">Buscar</button>
                </form>

                <button
                    @click="showCreate = true"
                    class="ml-4 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition flex items-center space-x-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Novo Usuário</span>
                </button>
            </div>

            <table class="min-w-full border">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="px-3 py-2 border">ID</th>
                        <th class="px-3 py-2 border">Nome</th>
                        <th class="px-3 py-2 border">Email</th>
                        <th class="px-3 py-2 border">Tipo</th>
                        <th class="px-3 py-2 border text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="border px-3 py-2">{{ $user->id }}</td>
                            <td class="border px-3 py-2">{{ $user->name }}</td>
                            <td class="border px-3 py-2">{{ $user->email }}</td>
                            <td class="border px-3 py-2">
                                @if($user->type == 1)
                                    <span class="text-red-600 font-semibold">Admin</span>
                                @elseif($user->type == 2)
                                    <span class="text-blue-600 font-semibold">Gestor</span>
                                @else
                                    <span class="text-gray-600 font-semibold">Visualização</span>
                                @endif
                            </td>
                            <td class="border px-3 py-2 text-center space-x-2">
                                <button
                                    @click="showEdit = true; editUser = {{ $user->toJson() }}"
                                    class="text-blue-600 hover:text-blue-800 transition"
                                    title="Editar"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.586-6.586a2 2 0 012.828 2.828L11.828 13.828a2 2 0 01-.707.465L7 15l1.707-4.121a2 2 0 01.293-.647z" />
                                    </svg>
                                </button>

                                <button
                                    @click="showDelete = true; deleteUser = {{ $user->toJson() }}"
                                    class="text-red-600 hover:text-red-800 transition"
                                    title="Deletar"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>

        <x-users.create-modal />
        <x-users.edit-modal />
        <x-users.delete-modal />
    </div>
</x-app-layout>
