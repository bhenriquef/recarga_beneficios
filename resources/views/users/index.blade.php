<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Usuários</h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto" x-data="{ showCreate: false, showEdit: false, editUser: {}, showDelete: false, deleteUser: {} }">

        <div class="bg-white shadow sm:rounded-lg p-6">

            <!-- 🔍 Buscar e botão novo -->
            <div class="flex justify-between items-center mb-4">
                <form method="GET" action="{{ route('users.index') }}" class="flex w-full sm:w-1/2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Buscar usuário..."
                        class="w-full rounded-l-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700 transition">
                        Buscar
                    </button>
                </form>

                <button @click="showCreate = true" class="ml-4 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Novo Usuário</span>
                </button>
            </div>

            <!-- Mensagens -->
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
            @endif

            <!-- 📋 Tabela -->
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
                            <td class="border px-3 py-2 text-center">
                                <!-- Botão Editar -->
                                <button
                                    @click="showEdit = true; editUser = {{ $user->toJson() }}"
                                    class="text-blue-600 hover:text-blue-800 transition mx-1"
                                    title="Editar"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.586-6.586a2 2 0 012.828 2.828L11.828 13.828a2 2 0 01-.707.465L7 15l1.707-4.121a2 2 0 01.293-.647z" />
                                    </svg>
                                </button>

                                <!-- Botão Deletar -->
                                <button
                                    @click="showDelete = true; deleteUser = {{ $user->toJson() }}"
                                    class="text-red-600 hover:text-red-800 transition mx-1"
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

        <!-- 🟢 Modal Criar Usuário -->
        <div x-show="showCreate" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="showCreate = false" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Novo Usuário</h2>
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700">Nome</label>
                            <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-gray-700">Email</label>
                            <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-gray-700">Tipo</label>
                            <select name="type" class="w-full border rounded px-3 py-2">
                                <option value="1">Admin</option>
                                <option value="2">Gestor</option>
                                <option value="3">Visualização</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700">Senha</label>
                                <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-gray-700">Confirmar Senha</label>
                                <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2" required>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 text-right space-x-2">
                        <button type="button" @click="showCreate = false" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancelar</button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 🟦 Modal Editar Usuário -->
        <div x-show="showEdit" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="showEdit = false" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Editar Usuário</h2>
                <form method="POST" :action="`/users/${editUser.id}`">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700">Nome</label>
                            <input type="text" name="name" x-model="editUser.name" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-gray-700">Email</label>
                            <input type="email" name="email" x-model="editUser.email" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-gray-700">Tipo</label>
                            <select name="type" x-model="editUser.type" class="w-full border rounded px-3 py-2">
                                <option value="1">Admin</option>
                                <option value="2">Gestor</option>
                                <option value="3">Visualização</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700">Nova Senha</label>
                                <input type="password" name="password" class="w-full border rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-gray-700">Confirmar Senha</label>
                                <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 text-right space-x-2">
                        <button type="button" @click="showEdit = false" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancelar</button>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 🔴 Modal Deletar Usuário -->
        <div x-show="showDelete" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="showDelete = false" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Confirmar exclusão</h2>
                <p class="text-gray-600 mb-6">Deseja realmente excluir o usuário <strong x-text="deleteUser.name"></strong>? Essa ação não pode ser desfeita.</p>

                <div class="flex justify-end space-x-3">
                    <button @click="showDelete = false" class="px-4 py-2 bg-gray-300 rounded-md text-gray-800 hover:bg-gray-400 transition">Cancelar</button>
                    <form method="POST" :action="`/users/${deleteUser.id}`">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">Deletar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
