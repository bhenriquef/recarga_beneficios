<div x-data="{ successMessage: '', errorMessage: '' }" @notify.window="
    if ($event.detail.type === 'success') {
        successMessage = $event.detail.message;
        setTimeout(() => successMessage = '', 4000);
    } else if ($event.detail.type === 'error') {
        errorMessage = $event.detail.message;
        setTimeout(() => errorMessage = '', 5000);
    }
" class="fixed top-5 right-5 space-y-2 z-50">

    <!-- ✅ Sucesso -->
    <template x-if="successMessage">
        <div
            x-transition
            class="bg-green-500 text-white px-5 py-3 rounded-lg shadow-lg flex items-center space-x-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span x-text="successMessage"></span>
        </div>
    </template>

    <!-- ❌ Erro -->
    <template x-if="errorMessage">
        <div
            x-transition
            class="bg-red-500 text-white px-5 py-3 rounded-lg shadow-lg flex items-center space-x-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span x-text="errorMessage"></span>
        </div>
    </template>
</div>
