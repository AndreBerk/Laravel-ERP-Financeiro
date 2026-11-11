<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40 print:hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <!-- Logo + links principais (desktop) -->
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="shrink-0 text-xl font-black text-gray-900 tracking-tight transition hover:opacity-80">
                    SISTEMA<span class="text-blue-600">FIN</span>
                </a>

                <div class="hidden lg:flex lg:ms-10 lg:space-x-6 h-full">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('dre.index')" :active="request()->routeIs('dre.*')">DRE</x-nav-link>
                    <x-nav-link :href="route('caixa.index')" :active="request()->routeIs('caixa.*')">Caixa Físico</x-nav-link>
                    <x-nav-link :href="route('despesas.index')" :active="request()->routeIs('despesas.*') || request()->routeIs('potes.*')">Despesas</x-nav-link>
                    <x-nav-link :href="route('importacao.index')" :active="request()->routeIs('importacao.*')">Importar</x-nav-link>

                    <!-- Grupo "Ajustes" -->
                    <div class="flex items-center h-full">
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center h-full px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none
                                    {{ request()->routeIs('taxas.*') || request()->routeIs('configuracoes.*') ? 'border-blue-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    Ajustes
                                    <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('taxas.index')">Taxas das Maquininhas</x-dropdown-link>
                                <x-dropdown-link :href="route('configuracoes.index')">Configurações e Saldos</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>

            <!-- Usuário (desktop) -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-2 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 focus:outline-none transition">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold">
                                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="max-w-[140px] truncate">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current text-gray-400" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Meu Perfil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 font-semibold hover:bg-red-50">
                                Sair do Sistema
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (celular/tablet) -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu responsivo -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t border-gray-100">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('dre.index')" :active="request()->routeIs('dre.*')">DRE</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('caixa.index')" :active="request()->routeIs('caixa.*')">Caixa Físico</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('despesas.index')" :active="request()->routeIs('despesas.*') || request()->routeIs('potes.*')">Despesas</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('importacao.index')" :active="request()->routeIs('importacao.*')">Importar (PDF/OFX)</x-responsive-nav-link>

            <p class="px-4 pt-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">Ajustes</p>
            <x-responsive-nav-link :href="route('taxas.index')" :active="request()->routeIs('taxas.*')">Taxas das Maquininhas</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('configuracoes.index')" :active="request()->routeIs('configuracoes.*')">Configurações e Saldos</x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-2 border-t border-gray-200">
            <div class="px-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white font-bold">
                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <div class="font-medium text-base text-gray-800 truncate">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500 truncate">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Meu Perfil</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 font-bold">
                        Sair do Sistema
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
