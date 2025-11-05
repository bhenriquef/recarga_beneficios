<div
    x-show="showCreate"
    x-transition.opacity.scale.80
    x-cloak
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
>
    <div @click.away="showCreate = false" class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">Novo Usuário</h2>

        @if ($errors->any())
            <div class="mb-4 text-red-600 bg-red-100 border border-red-300 rounded-md p-3">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('users.store') }}" @submit="loading = true">
            @csrf
            <input type="hidden" name="form_type" value="create">

            <x-users.form-fields />

            <div class="mt-6 text-right space-x-2">
                <button type="button" @click="showCreate = false" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancelar</button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Salvar</button>
            </div>
        </form>
    </div>
</div>
