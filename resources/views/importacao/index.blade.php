<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Central de Importação') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-4">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm mb-4">
                    <p class="font-bold">Ops! Encontramos um problema:</p>
                    <ul class="list-disc pl-5 mt-2 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($detalhes)
                @include('importacao.detalhes')
            @else
                <!-- ZONA 1: FORMULÁRIO DE UPLOAD -->
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">Novo Arquivo (CSV)</h3>

                    <form method="POST" action="{{ route('importacao.upload') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mês Referência</label>
                                <select name="mes" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-2" required>
                                    @for($i=1; $i<=12; $i++)
                                        <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>Mês {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                                <input type="number" name="ano" value="{{ date('Y') }}" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-2" required />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                                <select name="tipo_arquivo" id="tipo_arquivo" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-2" onchange="atualizarInstituicoes()" required>
                                    <option value="banco">Extrato Bancário</option>
                                    <option value="maquininha_vendas">Vendas (Passou na Máquina)</option>
                                    <option value="maquininha_recebidos">Recebimentos (Caiu na Conta)</option>
                                    <option value="sistema_vendas">Vendas Brutas (Sistema Loja)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Instituição</label>
                                <select name="conta_banco" id="conta_banco" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-2">
                                    <option value="bradesco">Bradesco</option>
                                    <option value="itau">Itaú</option>
                                </select>
                                <select name="conta_maquininha" id="conta_maquininha" class="hidden w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-2">
                                    <option value="cielo">Cielo</option>
                                    <option value="itau">Itaú (Laranjinha)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo CSV (Separado por vírgulas)</label>
                            <input type="file" name="arquivo_importacao" accept=".csv,.txt" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-200 p-1">
                        </div>

                        <div class="flex justify-end mt-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                                ⬆️ Importar e Processar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ZONA 2: ARQUIVOS IMPORTADOS -->
                <div class="space-y-6 mt-8">

                    <!-- Função auxiliar para renderizar as tabelas e botões -->
                    @php
                        function renderizarTabela($resumos, $origem, $titulo, $corBg, $corTexto, $corBorda) {
                            $filtrados = $resumos->where('origem', $origem);
                            echo '<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">';
                            echo '<div class="'.$corBg.' px-6 py-4 border-b '.$corBorda.'"><h3 class="text-lg font-bold '.$corTexto.'">'.$titulo.'</h3></div>';
                            echo '<div class="overflow-x-auto"><table class="w-full text-sm text-left text-gray-500">';
                            echo '<thead class="text-xs text-gray-700 uppercase bg-gray-50"><tr><th class="px-6 py-3">Referência</th><th class="px-6 py-3">Instituição</th><th class="px-6 py-3 text-center">Linhas Lidas</th><th class="px-6 py-3 text-right">Ação</th></tr></thead><tbody>';

                            if ($filtrados->isEmpty()) {
                                echo '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">Nenhum arquivo importado.</td></tr>';
                            } else {
                                foreach ($filtrados as $import) {
                                    $mes = str_pad($import->mes_referencia, 2, '0', STR_PAD_LEFT);
                                    $inst = strtoupper($import->conta_instituicao);
                                    $urlVer = route('importacao.index', ['ver_detalhes' => 1, 'origem' => $import->origem, 'conta' => $import->conta_instituicao, 'mes' => $import->mes_referencia, 'ano' => $import->ano_referencia]);
                                    $urlDel = route('importacao.destroy');
                                    $csrf = csrf_field();
                                    $method = method_field('DELETE');

                                    echo "<tr class='border-b hover:bg-gray-50'>
                                            <td class='px-6 py-4 font-medium text-gray-900'>{$mes}/{$import->ano_referencia}</td>
                                            <td class='px-6 py-4'>{$inst}</td>
                                            <td class='px-6 py-4 text-center font-bold'>{$import->total_linhas}</td>
                                            <td class='px-6 py-4 text-right'>
                                                <div class='flex justify-end space-x-3'>
                                                    <a href='{$urlVer}' class='text-blue-600 hover:text-blue-900 font-bold'>👁 Ver</a>
                                                    <form action='{$urlDel}' method='POST' onsubmit=\"return confirm('Excluir apagará todas as transações deste arquivo. Continuar?');\">
                                                        {$csrf} {$method}
                                                        <input type='hidden' name='origem' value='{$import->origem}'>
                                                        <input type='hidden' name='conta' value='{$import->conta_instituicao}'>
                                                        <input type='hidden' name='mes' value='{$import->mes_referencia}'>
                                                        <input type='hidden' name='ano' value='{$import->ano_referencia}'>
                                                        <button type='submit' class='text-red-600 hover:text-red-900 font-bold'>🗑️ Excluir</button>
                                                    </form>
                                                </div>
                                            </td>
                                          </tr>";
                                }
                            }
                            echo '</tbody></table></div></div>';
                        }
                    @endphp

                    {{ renderizarTabela($resumos, 'banco', '🏦 Extratos Bancários Importados', 'bg-blue-50', 'text-blue-800', 'border-blue-100') }}
                    {{ renderizarTabela($resumos, 'maquininha_vendas', '💳 Vendas da Maquininha Importadas', 'bg-green-50', 'text-green-800', 'border-green-100') }}
                    {{ renderizarTabela($resumos, 'maquininha_recebidos', '✅ Recebimentos Importados (Dinheiro na Conta)', 'bg-yellow-50', 'text-yellow-800', 'border-yellow-100') }}
                    {{ renderizarTabela($resumos, 'sistema_vendas', '🛒 Vendas Brutas Importadas (Sistema da Loja)', 'bg-purple-50', 'text-purple-800', 'border-purple-100') }}

                </div>
            @endif

        </div>
    </div>

    <script>
        function atualizarInstituicoes() {
            var tipo = document.getElementById('tipo_arquivo').value;
            var selectBanco = document.getElementById('conta_banco');
            var selectMaq = document.getElementById('conta_maquininha');

            if (tipo === 'banco') {
                selectBanco.classList.remove('hidden'); selectBanco.setAttribute('name', 'conta_banco');
                selectMaq.classList.add('hidden'); selectMaq.removeAttribute('name');
            } else if (tipo === 'maquininha_vendas' || tipo === 'maquininha_recebidos') {
                selectMaq.classList.remove('hidden'); selectMaq.setAttribute('name', 'conta_maquininha');
                selectBanco.classList.add('hidden'); selectBanco.removeAttribute('name');
            } else {
                selectBanco.classList.add('hidden'); selectBanco.removeAttribute('name');
                selectMaq.classList.add('hidden'); selectMaq.removeAttribute('name');
            }
        }
    </script>
</x-app-layout>
