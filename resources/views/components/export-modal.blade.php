<div class="hidden  sm:-my-px sm:ms-10 sm:flex items-center"
  x-data="exportModal"
>
  <!-- Botão -->
  <button
    @click="show()"
    class="inline-flex items-center space-x-4 px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none"
  >
    Gerar Excel
  </button>

  <!-- Modal -->
  <div
    x-show="isOpen"
    x-transition.opacity
    x-cloak
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    @keydown.escape.window="hide()"
    @click="hide()"
  >
    <div
      @click.stop
      class="bg-white p-6 rounded-lg shadow-xl w-96 text-center relative"
      x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0 scale-95"
      x-transition:enter-end="opacity-100 scale-100"
      x-transition:leave="transition ease-in duration-150"
      x-transition:leave-start="opacity-100 scale-100"
      x-transition:leave-end="opacity-0 scale-95"
    >
      <button
        @click="hide()"
        class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-lg font-bold"
        aria-label="Fechar"
      >&times;</button>

      <h2 class="text-lg font-semibold mb-3">Gerar arquivo Excel</h2>
      <p class="mb-6 text-gray-700" x-text="mensagem"></p>

      <div class="flex justify-center gap-3">
        <button
          x-show="links.ifood"
          @click="download('ifood')"
          class="bg-green-600 text-white px-4 py-2 rounded"
        >Baixar iFood</button>

        <button
          x-show="links.vr"
          @click="download('vr')"
          class="bg-blue-600 text-white px-4 py-2 rounded"
        >Baixar VR</button>

        <button
          @click="generate()"
          class="bg-purple-600 text-white px-4 py-2 rounded"
        >Gerar Novo</button>
      </div>

      <button @click="hide()" class="mt-4 text-gray-500">Cancelar</button>

      <!-- Loader -->
      <div
        x-show="loading"
        x-transition
        class="absolute inset-0 bg-white/70 flex items-center justify-center rounded-lg"
      >
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-500 border-t-transparent"></div>
      </div>
    </div>
  </div>
</div>
