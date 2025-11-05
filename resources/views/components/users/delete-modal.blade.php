{{-- Modal: Deletar usuário --}}
<div
    x-show="showDelete"
    x-transition.opacity.scale.80
    x-cloak
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    aria-modal="true"
    role="dialog"
>
    <div
        @click.away="showDelete = false"
        class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative"
    >
        {{-- Fechar --}}
        <button
            type="button"
            @click="showDelete = false"
            class="absolute right-3 top-3 text-gray-400 hover:text-gray-600"
            aria-label="Fechar"
        >
            &times;
        </button>

        {{-- Título --}}
        <h2 class="text-lg font-semibold text-gray-800 mb-3">
            Confirmar exclusão
        </h2>

        {{-- Mensagem --}}
        <p class="text-gray-600">
            Deseja realmente excluir o usuário
            <strong x-text="deleteUser.name || '—'"></strong>?
            <br>
            <span class="text-red-600 font-medium">Esta ação não poderá ser desfeita.</span>
        </p>

        {{-- Ações --}}
        <div class="mt-6 flex justify-end gap-3">
            <button
                type="button"
                @click="showDelete = false"
                class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 text-gray-800"
            >
                Cancelar
            </button>

            <form
                method="POST"
                :action="`{{ url('/users') }}/${deleteUser.id}`"
                @submit="loading = true"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white flex items-center gap-2"
                >
                    {{-- ícone lixeira --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                    </svg>
                    Deletar
                </button>
            </form>
        </div>
    </div>
</div>
