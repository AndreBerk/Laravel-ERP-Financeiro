<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('⚙️ Configuração de Taxas das Maquininhas') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <!-- FORMULÁRIO DE CADASTRO -->
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-700 mb-4">Cadastrar Nova Taxa Contratada</h3>

                <form action="{{ route('configuracoes.taxas.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instituição</label>
                            <select name="instituicao" required class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="itau">Itaú (Rede)</option>
                                <option value="cielo">Cielo</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bandeira</label>
                            <select name="bandeira" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="todas">Todas</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="visa">Visa</option>
                                <option value="elo">Elo</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade</label>
                            <select name="modalidade" required class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="debito">Débito</option>
                                <option value="credito_vista">Crédito à Vista</option>
                                <option value="credito_parcelado">Crédito Parcelado</option>
                                <option value="pix">PIX</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parcela (De)</label>
                            <input type="number" name="parcela_de" value="1" min="1" max="18" required class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parcela (Até)</label>
                            <input type="number" name="parcela_ate" value="1" min="1" max="18" required class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Taxa Acordada (%)</label>
                            <input type="number" name="taxa_percentual" step="0.01" placeholder="Ex: 1.99" required class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                            ➕ Salvar Taxa
                        </button>
                    </div>
                </form>
            </div>

            <!-- TABELA DE TAXAS CADASTRADAS -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mt-8">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">Tabela de Taxas Vigentes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="px-6 py-3">Instituição</th>
                                <th class="px-6 py-3">Bandeira</th>
                                <th class="px-6 py-3">Modalidade</th>
                                <th class="px-6 py-3">Faixa de Parcelas</th>
                                <th class="px-6 py-3 text-center">Taxa (%)</th>
                                <th class="px-6 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                       <tbody class="divide-y divide-gray-200">
                            @forelse($taxas as $taxa)
                                <tr class="hover:bg-gray-50">
                                    <!-- Instituição -->
                                    <td class="px-6 py-4 uppercase font-bold text-gray-800">
                                        {{ $taxa->instituicao }}
                                    </td>

                                    <!-- Bandeira -->
                                    <td class="px-6 py-4 uppercase text-gray-600 font-medium">
                                        {{ $taxa->bandeira }}
                                    </td>

                                    <!-- Modalidade (Débito ou Crédito com cor) -->
                                    <td class="px-6 py-4 uppercase text-sm">
                                        @if($taxa->modalidade === 'debito')
                                            <span class="bg-blue-100 text-blue-800 font-bold px-2 py-1 rounded text-xs">Débito</span>
                                        @else
                                            <span class="bg-green-100 text-green-800 font-bold px-2 py-1 rounded text-xs">Crédito</span>
                                        @endif
                                    </td>

                                    <!-- Parcelas -->
                                    <td class="px-6 py-4 text-center text-gray-700">
                                        @if($taxa->parcelas == 1)
                                            À vista (1x)
                                        @else
                                            {{ $taxa->parcelas }} vezes
                                        @endif
                                    </td>

                                    <!-- Taxa % Formatada -->
                                    <td class="px-6 py-4 text-center font-black text-red-600">
                                        {{ number_format($taxa->taxa, 2, ',', '.') }}%
                                    </td>

                                    <!-- Botão Excluir -->
                                    <td class="px-6 py-4 text-center">
                                        <form action="{{ route('configuracoes.taxas.destroy', $taxa->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta taxa?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold flex items-center justify-center gap-1 mx-auto">
                                                🗑️ Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">
                                        Nenhuma taxa cadastrada. Use o formulário para adicionar ou rode o script de importação.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
