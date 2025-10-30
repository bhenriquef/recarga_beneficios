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

                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')">
                        Empresas
                    </x-nav-link>
                </div>
                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                        Funcionarios
                    </x-nav-link>
                </div>
                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('benefits.index')" :active="request()->routeIs('benefits.*')">
                        Beneficios
                    </x-nav-link>
                </div>
                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center" style="z-index: 100">
                    <div x-data="{ openImport: false }" class="relative">
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
                            <div @click.away="openImport = false" class="bg-white rounded-lg w-full max-w-lg p-6 shadow-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold">Importar arquivo Excel</h3>
                                    <button @click="openImport = false" class="text-gray-500 hover:text-gray-700">&times;</button>
                                </div>

                                <form action="{{ route('imports.upload') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo (.xlsx .xls .csv)</label>
                                        <input required type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full" />
                                        @error('file') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex justify-end space-x-2">
                                        <button type="button" @click="openImport = false" class="px-4 py-2 rounded border">Cancelar</button>
                                        <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white">Importar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center" style="z-index: 100">
                    <div x-data="exportModal()" class="relative">
                        <!-- Botão -->
                        <button @click="open()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none"> Gerar Excel
                        </button>

                        <!-- Modal -->
                        <div x-show="openModal" x-transition.opacity x-cloak class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" @keydown.escape.window="close()" @click="close()">
                            <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-center relative" @click.stop>
                                <button @click="close()" class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-lg font-bold" aria-label="Fechar">&times;</button>

                                <h2 class="text-lg font-semibold mb-3">Gerar arquivo Excel</h2>
                                <p class="mb-6 text-gray-700" x-text="mensagem">Verificando arquivos...</p>

                                <div class="flex justify-center gap-3">
                                    <button x-show="links.ifood" @click="baixar('ifood')" class="bg-green-600 text-white px-4 py-2 rounded">Baixar iFood</button>
                                    <button x-show="links.vr" @click="baixar('vr')" class="bg-blue-600 text-white px-4 py-2 rounded">Baixar VR</button>
                                    <button @click="gerarNovos()" class="bg-purple-600 text-white px-4 py-2 rounded">Gerar Novo</button>
                                </div>
                                <button @click="close()" class="mt-4 text-gray-500">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
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
