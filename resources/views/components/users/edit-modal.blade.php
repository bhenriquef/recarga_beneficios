<div
    x-show="showEdit"
    x-transition.opacity.scale.80
    x-cloak
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
>
    <div @click.away="showEdit = false" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">Editar Usuário</h2>

        @if ($errors->any())
            <div class="mb-4 text-red-600 bg-red-100 border border-red-300 rounded-md p-3">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" :action="`/users/${editUser.id}`" @submit="loading = true">
            @csrf
            @method('PUT')
            <input type="hidden" name="form_type" value="edit">

            <x-users.form-fields :edit="true" />

            <div class="mt-6 text-right space-x-2">
                <button type="button" @click="showEdit = false" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Atualizar</button>
            </div>
        </form>
    </div>
</div>
