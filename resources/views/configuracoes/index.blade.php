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

                @if($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 mt-4 text-sm">
                        @foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach
                    </div>
                @endif

                <!-- SALDOS CADASTRADOS -->
                <h4 class="text-sm font-bold text-gray-700 mt-8 mb-3">Saldos cadastrados</h4>
                @if($saldos->isEmpty())
                    <p class="text-sm text-gray-400">Nenhum saldo inicial cadastrado ainda.</p>
                @else
                    <div class="overflow-x-auto border border-gray-100 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">Conta</th>
                                    <th class="px-4 py-2 text-left">Mês/Ano</th>
                                    <th class="px-4 py-2 text-right">Saldo</th>
                                    <th class="px-4 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($saldos as $saldo)
                                    <tr x-data="{ editando: false }">
                                        <!-- Visualização -->
                                        <td x-show="!editando" class="px-4 py-2 font-medium text-gray-800">{{ $contas[$saldo->conta_instituicao] ?? ucfirst($saldo->conta_instituicao) }}</td>
                                        <td x-show="!editando" class="px-4 py-2 text-gray-600">{{ str_pad($saldo->mes_referencia, 2, '0', STR_PAD_LEFT) }}/{{ $saldo->ano_referencia }}</td>
                                        <td x-show="!editando" class="px-4 py-2 text-right font-semibold {{ $saldo->valor < 0 ? 'text-red-600' : 'text-gray-800' }}">R$ {{ number_format($saldo->valor, 2, ',', '.') }}</td>
                                        <td x-show="!editando" class="px-4 py-2 text-right whitespace-nowrap">
                                            <button type="button" @click="editando = true" class="text-blue-600 hover:text-blue-800 font-semibold mr-3">Alterar</button>
                                            <form action="{{ route('configuracoes.saldo.excluir', $saldo) }}" method="POST" class="inline" onsubmit="return confirm('Excluir o saldo inicial de {{ str_pad($saldo->mes_referencia, 2, '0', STR_PAD_LEFT) }}/{{ $saldo->ano_referencia }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Excluir</button>
                                            </form>
                                        </td>

                                        <!-- Edição -->
                                        <td x-show="editando" x-cloak colspan="4" class="px-4 py-3 bg-blue-50/40">
                                            <form action="{{ route('configuracoes.saldo.atualizar', $saldo) }}" method="POST" class="flex flex-wrap gap-3 items-end">
                                                @csrf @method('PUT')
                                                <select name="conta_instituicao" class="rounded-md border-gray-300 text-sm">
                                                    @foreach($contas as $valor => $rotulo)
                                                        <option value="{{ $valor }}" @selected($saldo->conta_instituicao === $valor)>{{ $rotulo }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="number" name="mes_referencia" value="{{ $saldo->mes_referencia }}" min="1" max="12" required class="w-20 rounded-md border-gray-300 text-sm">
                                                <input type="number" name="ano_referencia" value="{{ $saldo->ano_referencia }}" required class="w-24 rounded-md border-gray-300 text-sm">
                                                <input type="text" name="valor" value="{{ number_format($saldo->valor, 2, ',', '.') }}" required class="w-36 rounded-md border-gray-300 text-sm">
                                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">Salvar</button>
                                                <button type="button" @click="editando = false" class="text-gray-500 hover:text-gray-700 text-sm">Cancelar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
