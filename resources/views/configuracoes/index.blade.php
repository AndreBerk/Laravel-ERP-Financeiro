<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configurações do Sistema') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Central de Parâmetros</h3>
                <p class="text-sm text-gray-500">
                    Gerencie os parâmetros contratuais, regras de validação e integrações que alimentam a inteligência do seu fluxo de caixa e DRE.
                </p>
            </div>

            <!-- FORMULÁRIO DE SALDO INICIAL -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">💰 Configurar Saldo Inicial do Mês</h3>

                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                <form action="{{ route('configuracoes.saldo.salvar') }}" method="POST" class="flex flex-wrap gap-4 items-end">
                    @csrf

                    <div class="w-full md:w-1/4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Conta/Banco</label>
                        <select name="conta_instituicao" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
                            <option value="bradesco">Bradesco</option>
                            <option value="itau">Itaú</option>
                        </select>
                    </div>

                    <div class="w-full md:w-1/6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                        <input type="number" name="mes_referencia" value="{{ date('m') }}" min="1" max="12" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
                    </div>

                    <div class="w-full md:w-1/6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                        <input type="number" name="ano_referencia" value="{{ date('Y') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
                    </div>

                    <div class="w-full md:w-1/4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor do Saldo (R$)</label>
                        <input type="text" name="valor" placeholder="Ex: 48.979,76" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
                    </div>

                    <div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition text-sm h-[38px]">
                            Salvar Saldo
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-200 flex flex-col justify-between">
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-4">
                            💳
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">Taxas das Maquininhas</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Cadastre as taxas acordadas por instituição (Itaú/Rede, Cielo), bandeiras e faixas de parcelamento para conferência e auditoria automática nos extratos de vendas.
                        </p>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end">
                        <a href="{{ route('configuracoes.taxas') }}" class="inline-flex items-center text-sm font-bold text-blue-600 hover:text-blue-800 transition">
                            Configurar Taxas &rarr;
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-200 flex flex-col justify-between opacity-80">
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-2xl mb-4">
                            🏷️
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">Categorias e Plano DRE</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Gerencie as categorias de receitas e custos, além das palavras-chave usadas pelo leitor de inteligência para classificar os extratos bancários.
                        </p>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs bg-gray-200 text-gray-600 font-semibold px-2 py-1 rounded">Em Breve</span>
                        <span class="text-xs text-gray-400 font-bold">Bloqueado</span>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-200 flex flex-col justify-between opacity-80">
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-2xl mb-4">
                            🏦
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">Contas e Instituições</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Cadastro e conciliação de contas correntes bancárias (Bradesco, Itaú) e saldos iniciais de abertura de período.
                        </p>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs bg-gray-200 text-gray-600 font-semibold px-2 py-1 rounded">Em Breve</span>
                        <span class="text-xs text-gray-400 font-bold">Bloqueado</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
