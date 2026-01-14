<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo style="background-color: transparent" class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links + Import Button -->
                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>

                @if(Auth::user()?->type === 1)
                    <!-- Submenu: Administracao -->
                    <div x-data="{ openAdmin: false }" class="hidden sm:flex sm:-my-px sm:ms-10 items-center relative">
                        <button
                            @click="openAdmin = !openAdmin"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-indigo-600 focus:outline-none transition"
                        >
                            Administracao
                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.25 7.5l4.5 4.5 4.5-4.5" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div
                            x-show="openAdmin"
                            @click.away="openAdmin = false"
                            x-transition
                            class="absolute top-10 left-0 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-20"
                        >
                            <a href="{{ route('companies.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('companies.*') ? 'bg-gray-100 font-semibold' : '' }}">
                            Empresas
                            </a>
                            <a href="{{ route('employees.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('employees.*') ? 'bg-gray-100 font-semibold' : '' }}">
                            Funcionarios
                            </a>
                            <a href="{{ route('benefits.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('benefits.*') ? 'bg-gray-100 font-semibold' : '' }}">
                            Beneficios
                            </a>
                            <a href="{{ route('users.index') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('users.*') ? 'bg-gray-100 font-semibold' : '' }}">
                            Usuarios
                            </a>
                            <a href="{{ route('balance.import') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('balance.import') ? 'bg-gray-100 font-semibold' : '' }}">
                            Gestão de saldo
                            </a>
                        </div>
                    </div>
                @endif

                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('excelCustomizado')" :active="request()->routeIs('excelCustomizado')">
                        {{ __('Excel customizado') }}
                    </x-nav-link>
                </div>

                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    <div x-data="{
                            openImport: {{ session()->has('log_download') ? 'true' : 'false' }},
                            importLoading: false
                        }">
                        <button
                            @click="openImport = true"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none"
                        >
                            Importar dados
                        </button>
                        <div
                            x-show="openImport"
                            x-cloak
                            class="fixed inset-0 z-50 flex items-center justify-center p-4"
                            style="background: rgba(0,0,0,0.4);"
                        >
                            <div @click.away="openImport = false" class="bg-white rounded-lg w-full max-w-lg p-6 shadow-lg relative overflow-hidden">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold">Importar arquivo Excel</h3>
                                    <button @click="openImport = false" class="text-gray-500 hover:text-gray-700">&times;</button>
                                </div>

                                <form action="{{ route('imports.upload') }}" method="POST" enctype="multipart/form-data" @submit="importLoading = true">
                                    @csrf

                                    {{-- NOVO: Tipo --}}
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de planilha</label>
                                        <select required name="type" class="block w-full rounded border-gray-300">
                                            <option value="" selected disabled>Selecione...</option>
                                            <option value="funcionarios_vr">Funcionarios VR</option>
                                            <option value="dados_reaproveitamento">Dados Reaproveitamento</option>
                                            <option value="vt_ifood_geral">Planilha Geral VT (iFood)</option>
                                            <option value="vale_alimentacao">Vale Alimentação</option>
                                            <option value="recem_admitidos">Recem Admitidos</option>
                                            {{-- <option value="saldo_livre_ifood">Saldo Livre Ifood</option>
                                            <option value="saldo_mobilidade_ifood">Saldo Mobilidade Ifood</option> --}}
                                        </select>
                                        @error('type') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Competência (mês)</label>

                                        <input
                                            required
                                            type="text"
                                            name="competence_month"
                                            x-data
                                            value="{{ now()->format('Y-m') }}"
                                            x-monthpicker="{}"
                                            autocomplete="false"
                                            placeholder="mm/aaaa"
                                            class="block w-full rounded border-gray-300"
                                        />


                                        @error('competence_month')
                                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>


                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo (.xlsx .xls .csv)</label>
                                        <input required type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full" />
                                        @error('file') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex justify-end space-x-2">
                                        <button type="button" @click="openImport = false" class="px-4 py-2 rounded border">Cancelar</button>
                                        <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white" :class="{ 'opacity-60 cursor-not-allowed': importLoading }" :disabled="importLoading">
                                            <span x-show="!importLoading">Importar</span>
                                            <span x-show="importLoading" class="inline-flex items-center gap-2">
                                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
                                                </svg>
                                                Enviando...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                                @if(session('log_download'))
                                    <div class="mt-4 rounded-md border border-yellow-200 bg-yellow-50 p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-yellow-800">
                                                    Importação concluída com avisos
                                                </p>
                                                <p class="text-xs text-yellow-700 mt-1">
                                                    Alguns registros não foram encontrados no sistema. Você pode baixar o relatório para conferir.
                                                </p>
                                            </div>

                                            <a
                                                href="{{ session('log_download') }}"
                                                class="shrink-0 inline-flex items-center px-3 py-2 rounded bg-yellow-600 text-white text-sm hover:bg-yellow-700"
                                            >
                                                Baixar relatório
                                            </a>
                                        </div>
                                    </div>

                                    <script>
                                        document.addEventListener('alpine:init', () => {
                                            // abre o modal automaticamente quando voltar com relatório
                                            // (assim o usuário vê o botão sem precisar clicar em "Importar" de novo)
                                            const open = () => {
                                                // tenta setar a variável no escopo mais próximo
                                                // se openImport estiver em outro x-data, você pode remover esse script e abrir manualmente
                                                window.openImport = true;
                                            };
                                            open();
                                        });
                                    </script>
                                @endif

                                <div
                                    x-show="importLoading"
                                    x-cloak
                                    class="absolute inset-0 bg-white bg-opacity-80 flex flex-col items-center justify-center space-y-3 text-indigo-700"
                                >
                                    <svg class="animate-spin h-10 w-10 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
                                    </svg>
                                    <p class="text-sm font-medium">Enviando arquivo, aguarde...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div x-data="syncDatabase()" class="ml-4 relative">
                    <button
                        @click="startSync"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md
                            text-white bg-green-600 hover:bg-green-700 focus:outline-none"
                    >
                        Sincronizar Banco
                    </button>

                    <!-- Modal de progresso -->
                    <div
                        x-show="open"
                        x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        style="background: rgba(0,0,0,0.4);"
                    >
                        <div class="relative bg-white rounded-lg w-full max-w-md p-6 shadow-lg overflow-hidden">
                            <h3 class="text-lg font-semibold mb-2">Sincronizando banco de dados</h3>

                            <p class="text-sm text-gray-600 mb-4">
                                Esse processo pode demorar de <b>10 a 15 minutos</b>. Por favor, não feche esta página.
                            </p>

                            <div
                                x-show="importLoading"
                                class="bg-white bg-opacity-80 flex flex-col items-center justify-center space-y-3 text-indigo-700 py-6"
                            >
                                <svg class="animate-spin h-10 w-10 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v2a6 6 0 00-6 6H4z"></path>
                                </svg>
                                <p class="text-sm font-medium">Sincronizando dados, aguarde...</p>
                            </div>
                        </div>
                    </div>

                    {{-- <div
                        x-show="open"
                        x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        style="background: rgba(0,0,0,0.4);"
                    >
                        <div class="bg-white rounded-lg w-full max-w-xl p-6 shadow-lg">
                            <h3 class="text-lg font-semibold mb-4">Sincronizando banco de dados</h3>

                            <!-- Barra de progresso -->
                            <div class="w-full bg-gray-200 rounded-full h-4 mb-4">
                                <div class="bg-green-600 h-4 rounded-full" :style="width: %"></div>
                            </div>

                            <p class="text-sm font-medium mb-2">Progresso: <span x-text="progress"></span>%</p>

                            <!-- Logs -->
                            <div class="bg-gray-100 p-3 rounded h-48 overflow-auto text-sm font-mono border">
                                <template x-for="line in logs">
                                    <div x-text="line"></div>
                                </template>
                            </div>

                            <div class="mt-4 text-right">
                                <button
                                    @click="open = false"
                                    class="px-4 py-2 rounded border bg-gray-200 hover:bg-gray-300"
                                    :disabled="progress < 100"
                                >
                                    Fechar
                                </button>
                            </div>
                        </div>
                    </div> --}}
                </div>

                </div>
                <x-export-modal />
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link> --}}

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Sair') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
<script>
// function syncDatabase() {
//     return {
//         open: false,
//         progress: 0,
//         logs: [],

//         startSync() {
//             this.open = true;
//             this.progress = 0;
//             this.logs = [];

//             axios.post('{{ route('database.sync') }}')
//                 .then(() => {
//                     this.listenForUpdates();
//                 })
//                 .catch(() => {
//                     this.logs.push('Erro ao iniciar sincronizacao.');
//                 });
//         },

//         listenForUpdates() {
//             // EventSource mantem conexao aberta com o backend
//             const es = new EventSource('/sync-database-stream');

//             es.onmessage = (e) => {
//                 const data = JSON.parse(e.data);

//                 if (data.progress !== undefined) {
//                     this.progress = data.progress;
//                 }

//                 if (data.log) {
//                     this.logs.push(data.log);
//                 }

//                 if (data.eta) {
//                     this.eta = data.eta;
//                 }

//                 if (data.finished) {
//                     es.close();
//                     this.logs.push('Finalizado!');
//                 }
//             };
//         }
//     };
// }
function syncDatabase() {
    return {
        open: false,
        importLoading: false,
        pollTimer: null,

        startSync() {
            this.open = true;
            this.importLoading = true;

            axios.post('{{ route('database.sync') }}')
                .then(() => {
                    this.startPolling();
                })
                .catch(() => {
                    this.importLoading = false;
                    this.open = false;
                    // aqui você pode disparar seu toast
                    // toast.error('Erro ao iniciar sincronização.');

                    window.dispatchEvent(
                        new CustomEvent("notify", {
                            detail: {
                                type: "error",
                                message: "Erro ao iniciar sincronização.",
                            },
                        })
                    );
                });
        },

        startPolling() {
            if (this.pollTimer) clearInterval(this.pollTimer);

            this.pollTimer = setInterval(async () => {
                try {
                    const { data } = await axios.get('{{ route('database.sync.status') }}');

                    if (data.finished) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;

                        this.importLoading = false;
                        this.open = false;

                        window.dispatchEvent(
                            new CustomEvent("notify", {
                                detail: {
                                    type: "success",
                                    message: "Sincronização finalizada!",
                                },
                            })
                        );
                    }

                    if (data.error) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;

                        this.importLoading = false;
                        this.open = false;

                        // toast.error(data.error);

                        window.dispatchEvent(
                            new CustomEvent("notify", {
                                detail: {
                                    type: "error",
                                    message: data?.error || "Erro ao sincronizar dados!.",
                                },
                            })
                        );
                    }
                } catch (e) {
                    // se falhar a consulta, só continua tentando (sem travar usuário)
                }
            }, 5000);
        },
    };
}

</script>
