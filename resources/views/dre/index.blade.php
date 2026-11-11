<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Demonstrativo de Resultados (DRE)') }}
        </h2>
    </x-slot>

    @php
        $fmt = fn($v) => number_format((float) $v, 2, ',', '.');
        $nomesMeses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $mesesCurtos = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    @endphp

    <style>
        .caixa-tooltip { visibility: hidden; opacity: 0; position: absolute; bottom: 140%; left: -10px; z-index: 9999; transition: all 0.2s ease-in-out; width: 280px; }
        .icone-ajuda:hover .caixa-tooltip { visibility: visible; opacity: 1; bottom: 150%; }
        .tabela-anual th, .tabela-anual td { padding: 5px 4px; font-size: 10px; text-align: right; white-space: nowrap; }
        .tabela-anual td:first-child, .tabela-anual th:first-child { text-align: left; font-weight: 600; }
    </style>

    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            <!-- BARRA DE FILTROS -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Resultado Financeiro</h3>
                    <p class="text-sm text-gray-500">Fluxo de caixa, saldos reais e previsão de recebimentos, com base nos extratos e na maquininha.</p>
                </div>

                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('dre.index') }}" class="flex items-center gap-3">
                        <select name="mes" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 text-sm py-2">
                            <option value="todos" {{ $mesInput === 'todos' ? 'selected' : '' }}>Ano todo (comparativo)</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $mesInput == $i ? 'selected' : '' }}>{{ $nomesMeses[$i] }}</option>
                            @endfor
                        </select>
                        <select name="ano" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 text-sm py-2">
                            @for ($i = date('Y') - 1; $i <= date('Y') + 2; $i++)
                                <option value="{{ $i }}" {{ $ano == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                        <button type="submit" class="bg-gray-800 hover:bg-black text-white font-bold py-2 px-6 rounded shadow transition text-sm">Filtrar</button>
                    </form>
                    <a href="{{ route('dre.imprimir', ['mes' => $mesInput, 'ano' => $ano]) }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
                        Imprimir
                    </a>
                </div>
            </div>

            @if($isAnual)
                <!-- ============================================== -->
                <!-- LAYOUT ANUAL -->
                <!-- ============================================== -->
                @php
                    $m12 = $mesesData[12];
                    // [rótulo, chave do mês, total (null = "-"), classe do texto, colorir por sinal?]
                    $secoes = [
                        ['titulo' => 'PAINEL COMERCIAL: MAQUININHA (INFORMATIVO)', 'cabecalho' => 'bg-purple-100 text-purple-900 border-purple-200', 'linhas' => [
                            ['Vendas no mês (bruto)', 'vendasBrutas', $totais['vendasBrutas'], 'text-gray-600', false],
                            ['Recebido no mês (bruto)', 'recebimentosBrutos', $totais['recebimentosBrutos'], 'text-gray-600', false],
                            ['Taxas retidas (-)', 'taxasMaquininha', $totais['taxasMaquininha'], 'text-red-600', false],
                            ['A receber no fim do mês (bruto)', 'aReceberBruto', $m12['aReceberBruto'], 'text-purple-700 font-bold', false],
                            ['A receber estimado (líquido)', 'aReceberLiquido', $m12['aReceberLiquido'], 'text-purple-700 font-bold', false],
                        ]],
                        ['titulo' => 'DINHEIRO NOVO E RESULTADO (EXTRATOS BANCÁRIOS)', 'cabecalho' => 'bg-green-100 text-green-900 border-green-200', 'linhas' => [
                            ['1. Dinheiro novo (entradas)', 'receitaCaixa', $totais['receitaCaixa'], 'text-green-800 font-bold', false],
                            ['   Maquininha depositada', 'maquininhaNoBanco', $totais['maquininhaNoBanco'], 'text-gray-600', false],
                            ['   PIX e outros', 'pixOutros', $totais['pixOutros'], 'text-gray-600', false],
                            ['2. Saídas (despesas) (-)', 'saidasBanco', $totais['saidasBanco'], 'text-red-700', false],
                            ['3. RESULTADO LÍQUIDO', 'lucroLiquido', $totais['lucroLiquido'], 'font-black', true],
                        ]],
                        ['titulo' => 'SALDO TOTAL EM CONTA (BRADESCO + ITAÚ)', 'cabecalho' => 'bg-gray-200 text-gray-900 border-gray-300', 'linhas' => [
                            ['Saldo inicial', 'saldoInicialTotal', null, 'text-gray-500', false],
                            ['Entradas (+)', 'entradasTotalContas', $totais['entradasTotalContas'], 'text-green-700', false],
                            ['Saídas (-)', 'saidasTotalContas', $totais['saidasTotalContas'], 'text-red-600', false],
                            ['Saldo final do mês', 'saldoFinalTotal', $m12['saldoFinalTotal'], 'text-gray-900 font-black', false],
                        ]],
                        ['titulo' => 'FLUXO DE CAIXA: CONTA BRADESCO', 'cabecalho' => 'bg-blue-100 text-blue-900 border-blue-200', 'linhas' => [
                            ['Saldo inicial', 'saldoInicialBradesco', null, 'text-gray-500', false],
                            ['Entradas (+)', 'entradasBradesco', $totais['entradasBradesco'], 'text-green-700', false],
                            ['Saídas (-)', 'saidasBradesco', $totais['saidasBradesco'], 'text-red-600', false],
                            ['Saldo final do mês', 'saldoFinalBradesco', $m12['saldoFinalBradesco'], 'text-blue-900 font-bold', false],
                        ]],
                        ['titulo' => 'FLUXO DE CAIXA: CONTA ITAÚ', 'cabecalho' => 'bg-orange-100 text-orange-900 border-orange-200', 'linhas' => [
                            ['Saldo inicial', 'saldoInicialItau', null, 'text-gray-500', false],
                            ['Entradas (+)', 'entradasItau', $totais['entradasItau'], 'text-green-700', false],
                            ['Saídas (-)', 'saidasItau', $totais['saidasItau'], 'text-red-600', false],
                            ['Saldo final do mês', 'saldoFinalItau', $m12['saldoFinalItau'], 'text-orange-900 font-bold', false],
                        ]],
                    ];
                @endphp

                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden w-full">
                    <div class="bg-gray-800 px-4 py-3">
                        <h3 class="text-base font-bold text-white">Comparativo mensal e fluxo de caixa: {{ $ano }}</h3>
                    </div>
                    <div class="w-full overflow-x-auto">
                        <table class="w-full tabela-anual table-fixed">
                            <thead class="bg-gray-100 text-[9px] uppercase text-gray-700">
                                <tr>
                                    <th class="w-1/6 bg-gray-100 border-r border-gray-200">Indicador</th>
                                    @foreach($mesesCurtos as $nome) <th class="w-[6%]">{{ $nome }}</th> @endforeach
                                    <th class="w-[8%] bg-gray-200 font-black">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($secoes as $secao)
                                    <tr><td colspan="14" class="{{ $secao['cabecalho'] }} font-bold text-center border-y-2 py-1.5">{{ $secao['titulo'] }}</td></tr>
                                    @foreach($secao['linhas'] as [$rotulo, $chave, $total, $classe, $porSinal])
                                        <tr class="{{ $porSinal ? 'bg-gray-800 text-white' : 'bg-white ' . $classe }}">
                                            <td class="{{ $porSinal ? 'bg-gray-800 border-r border-gray-700' : 'bg-gray-50 border-r border-gray-200' }}">{{ $rotulo }}</td>
                                            @for($m = 1; $m <= 12; $m++)
                                                @php $v = $mesesData[$m][$chave]; @endphp
                                                <td class="{{ $porSinal ? ($v < 0 ? 'text-red-400 font-bold' : 'text-green-400 font-bold') : '' }}">{{ $fmt($v) }}</td>
                                            @endfor
                                            <td class="{{ $porSinal ? 'bg-gray-900 font-black ' . ($total < 0 ? 'text-red-400' : 'text-green-400') : 'bg-gray-100 font-bold' }}">
                                                {{ $total === null ? '-' : $fmt($total) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="px-4 py-3 text-[11px] text-gray-500 border-t border-gray-100">
                        Dinheiro novo = entradas reais nos extratos (PIX + depósitos da maquininha), sem saldo anterior e sem transferências entre contas próprias.
                        "A receber" considera as vendas do ano ainda não depositadas.
                    </p>
                </div>

            @else
                <!-- ============================================== -->
                <!-- LAYOUT MENSAL -->
                <!-- ============================================== -->

                <!-- INDICADORES PRINCIPAIS -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="bg-gray-900 rounded-lg p-5 shadow-sm">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">Saldo total em conta</p>
                        <p class="text-2xl font-black text-white mt-1">R$ {{ $fmt($saldoFinalTotal) }}</p>
                        <p class="text-xs text-gray-400 mt-2">Bradesco R$ {{ $fmt($saldoFinalBradesco) }} &nbsp;+&nbsp; Itaú R$ {{ $fmt($saldoFinalItau) }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-5 shadow-sm border border-green-200 border-t-4 border-t-green-500">
                        <p class="text-[11px] font-bold text-green-700 uppercase tracking-wide">Dinheiro novo no mês</p>
                        <p class="text-2xl font-black text-green-800 mt-1">R$ {{ $fmt($receitaCaixa) }}</p>
                        <p class="text-xs text-gray-500 mt-2">Recebido de fato nos bancos neste mês</p>
                    </div>
                    <div class="bg-white rounded-lg p-5 shadow-sm border border-purple-200 border-t-4 border-t-purple-500">
                        <p class="text-[11px] font-bold text-purple-700 uppercase tracking-wide">A receber da maquininha</p>
                        <p class="text-2xl font-black text-purple-800 mt-1">R$ {{ $fmt($aReceberLiquido) }}</p>
                        <p class="text-xs text-gray-500 mt-2">Estimado após taxas &middot; bruto R$ {{ $fmt($aReceberBruto) }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-5 shadow-sm border border-blue-200 border-t-4 border-t-blue-500">
                        <p class="text-[11px] font-bold text-blue-700 uppercase tracking-wide">Previsto total do mês</p>
                        <p class="text-2xl font-black text-blue-800 mt-1">R$ {{ $fmt($previstoTotal) }}</p>
                        <p class="text-xs text-gray-500 mt-2">Dinheiro novo + a receber da maquininha</p>
                    </div>
                </div>

                <!-- DINHEIRO NOVO -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-visible">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <h3 class="text-lg font-bold text-gray-800">Dinheiro novo do mês</h3>
                        <div class="icone-ajuda relative cursor-help flex items-center justify-center w-5 h-5 rounded-full bg-gray-200 text-gray-600 text-xs font-bold">
                            i
                            <div class="caixa-tooltip bg-gray-900 text-white text-xs p-3 rounded shadow-lg font-normal leading-relaxed normal-case">
                                Soma das entradas dos extratos (PIX e depósitos da maquininha) no mês. Não inclui o saldo que já estava na conta nem transferências entre suas contas. É este valor a base da divisão dos potes.
                                <div class="absolute -bottom-1 left-3 w-3 h-3 bg-gray-900 transform rotate-45"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase">Total recebido (dinheiro novo)</p>
                            <p class="text-4xl font-black text-green-700 mt-1">R$ {{ $fmt($receitaCaixa) }}</p>
                        </div>
                        <div class="lg:col-span-2 space-y-4">
                            @php
                                $pctMaq = $receitaCaixa > 0 ? ($maquininhaNoBanco / $receitaCaixa) * 100 : 0;
                                $pctPix = $receitaCaixa > 0 ? 100 - $pctMaq : 0;
                            @endphp
                            <div class="w-full h-3 rounded-full bg-gray-100 overflow-hidden flex">
                                <div class="bg-purple-500 h-3" style="width: {{ number_format($pctMaq, 2, '.', '') }}%"></div>
                                <div class="bg-green-500 h-3" style="width: {{ number_format($pctPix, 2, '.', '') }}%"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="flex items-start gap-2">
                                    <span class="mt-1.5 w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                    <div>
                                        <p class="text-gray-500 text-xs">Maquininha (débito e crédito depositados)</p>
                                        <p class="font-bold text-gray-800">R$ {{ $fmt($maquininhaNoBanco) }} <span class="text-xs font-normal text-gray-400">({{ number_format($pctMaq, 0) }}%)</span></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="mt-1.5 w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                    <div>
                                        <p class="text-gray-500 text-xs">PIX e outras entradas</p>
                                        <p class="font-bold text-gray-800">R$ {{ $fmt($pixOutros) }} <span class="text-xs font-normal text-gray-400">({{ number_format($pctPix, 0) }}%)</span></p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Entrou no Bradesco R$ {{ $fmt($entradasBradesco) }} e no Itaú R$ {{ $fmt($entradasItau) }} (inclui eventuais transferências entre as contas).</p>
                        </div>
                    </div>
                </div>

                <!-- MAQUININHA: PREVISTO x RECEBIDO -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800">Maquininha: previsto x recebido</h3>
                        <p class="text-xs text-gray-500">Débito, crédito à vista e parcelado. Informativo, não altera o resultado.</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                            <div class="bg-gray-50 p-4 rounded border border-gray-100">
                                <p class="text-[11px] font-bold text-gray-500 uppercase mb-1">Vendas no mês (bruto)</p>
                                <p class="text-lg font-bold text-gray-800">R$ {{ $fmt($vendasBrutas) }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded border border-gray-100">
                                <p class="text-[11px] font-bold text-gray-500 uppercase mb-1">Recebido no mês (bruto)</p>
                                <p class="text-lg font-bold text-gray-800">R$ {{ $fmt($recebimentosBrutos) }}</p>
                            </div>
                            <div class="bg-red-50 p-4 rounded border border-red-100">
                                <p class="text-[11px] font-bold text-red-600 uppercase mb-1">Taxas retidas</p>
                                <p class="text-lg font-bold text-red-700">R$ {{ $fmt($taxasMaquininha) }}</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded border border-green-100">
                                <p class="text-[11px] font-bold text-green-700 uppercase mb-1">Líquido recebido</p>
                                <p class="text-lg font-bold text-green-800">R$ {{ $fmt($recebimentosLiquidos) }}</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded border border-purple-100">
                                <p class="text-[11px] font-bold text-purple-700 uppercase mb-1">A receber (líquido est.)</p>
                                <p class="text-lg font-bold text-purple-800">R$ {{ $fmt($aReceberLiquido) }}</p>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Já recebido das vendas do ano até este mês: <strong class="text-gray-700">R$ {{ $fmt($recebidoAcum) }}</strong> de R$ {{ $fmt($vendasAcum) }} (bruto)</span>
                                <span class="font-bold text-gray-700">{{ number_format($pctRecebido, 1, ',', '.') }}%</span>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-purple-100 overflow-hidden">
                                <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ number_format($pctRecebido, 2, '.', '') }}%"></div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2">A receber = vendas acumuladas no ano menos o que já foi depositado, descontada a taxa média do período. Parcelas de vendas de anos anteriores não entram.</p>
                        </div>
                    </div>
                </div>

                <!-- CONTAS BANCÁRIAS -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-5 border border-blue-200 shadow-sm border-t-4 border-t-blue-500">
                        <h4 class="font-bold text-blue-900 border-b border-gray-100 pb-2 mb-3">Fluxo Bradesco</h4>
                        <div class="flex justify-between text-sm mb-1"><span class="text-gray-500">Saldo inicial</span> <span class="font-bold">R$ {{ $fmt($saldoInicialBradesco) }}</span></div>
                        <div class="flex justify-between text-sm mb-1"><span class="text-green-600">Entradas (+)</span> <span class="font-bold text-green-700">R$ {{ $fmt($entradasBradesco) }}</span></div>
                        <div class="flex justify-between text-sm mb-2"><span class="text-red-500">Saídas (-)</span> <span class="font-bold text-red-600">R$ {{ $fmt($saidasBradesco) }}</span></div>
                        <div class="flex justify-between text-base pt-2 border-t border-gray-100"><span class="font-bold text-blue-900">Saldo final</span> <span class="font-black text-blue-900">R$ {{ $fmt($saldoFinalBradesco) }}</span></div>
                    </div>

                    <div class="bg-white rounded-lg p-5 border border-orange-200 shadow-sm border-t-4 border-t-orange-500">
                        <h4 class="font-bold text-orange-900 border-b border-gray-100 pb-2 mb-3">Fluxo Itaú</h4>
                        <div class="flex justify-between text-sm mb-1"><span class="text-gray-500">Saldo inicial</span> <span class="font-bold">R$ {{ $fmt($saldoInicialItau) }}</span></div>
                        <div class="flex justify-between text-sm mb-1"><span class="text-green-600">Entradas (+)</span> <span class="font-bold text-green-700">R$ {{ $fmt($entradasItau) }}</span></div>
                        <div class="flex justify-between text-sm mb-2"><span class="text-red-500">Saídas (-)</span> <span class="font-bold text-red-600">R$ {{ $fmt($saidasItau) }}</span></div>
                        <div class="flex justify-between text-base pt-2 border-t border-gray-100"><span class="font-bold text-orange-900">Saldo final</span> <span class="font-black text-orange-900">R$ {{ $fmt($saldoFinalItau) }}</span></div>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-5 shadow-sm border-t-4 border-t-gray-500">
                        <h4 class="font-bold text-white border-b border-gray-700 pb-2 mb-3">Total das contas</h4>
                        <div class="flex justify-between text-sm mb-1"><span class="text-gray-400">Saldo inicial</span> <span class="font-bold text-gray-200">R$ {{ $fmt($saldoInicialTotal) }}</span></div>
                        <div class="flex justify-between text-sm mb-1"><span class="text-green-400">Entradas (+)</span> <span class="font-bold text-green-400">R$ {{ $fmt($entradasTotalContas) }}</span></div>
                        <div class="flex justify-between text-sm mb-2"><span class="text-red-400">Saídas (-)</span> <span class="font-bold text-red-400">R$ {{ $fmt($saidasTotalContas) }}</span></div>
                        <div class="flex justify-between text-base pt-2 border-t border-gray-700"><span class="font-bold text-white">Saldo final</span> <span class="font-black text-white">R$ {{ $fmt($saldoFinalTotal) }}</span></div>
                    </div>
                </div>

                <!-- DRE -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-visible">
                    <div class="bg-gray-800 px-6 py-4 flex justify-between items-center rounded-t-lg">
                        <h3 class="text-lg font-bold text-white">Demonstrativo de fluxo de caixa: {{ $nomesMeses[$mes] }}/{{ $ano }}</h3>
                        <span class="text-xs text-gray-300">Margem: {{ number_format($margemLucro, 1, ',', '.') }}%</span>
                    </div>

                    <table class="w-full text-sm text-left">
                        <tbody class="divide-y divide-gray-100">
                            <tr class="bg-green-50/50">
                                <td class="px-6 py-4 font-bold text-gray-800 w-2/3">1. DINHEIRO NOVO (ENTRADAS BRADESCO + ITAÚ)</td>
                                <td class="px-6 py-4 font-bold text-green-700 text-right">R$ {{ $fmt($receitaCaixa) }}</td>
                            </tr>
                            <tr>
                                <td class="px-8 py-2 text-gray-600">Maquininha depositada</td>
                                <td class="px-6 py-2 text-gray-500 text-right">R$ {{ $fmt($maquininhaNoBanco) }}</td>
                            </tr>
                            <tr>
                                <td class="px-8 py-2 text-gray-600">PIX e outras entradas</td>
                                <td class="px-6 py-2 text-gray-500 text-right">R$ {{ $fmt($pixOutros) }}</td>
                            </tr>

                            <tr class="bg-red-50/30 border-t-2 border-gray-100">
                                <td class="px-6 py-4 font-bold text-gray-800">2. SAÍDAS DE CAIXA (POR CATEGORIA)</td>
                                <td class="px-6 py-4 font-bold text-red-600 text-right">- R$ {{ $fmt($saidasBanco) }}</td>
                            </tr>
                            @forelse($categoriasDespesas as $despesa)
                                <tr>
                                    <td class="px-8 py-2.5 text-gray-600 font-medium">{{ $despesa->categoria_nome }}</td>
                                    <td class="px-6 py-2.5 text-gray-500 text-right">R$ {{ $fmt($despesa->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-8 py-3 text-gray-400 text-xs italic">Nenhuma despesa registrada neste período.</td></tr>
                            @endforelse

                            <tr class="bg-gray-800 text-white border-t-2 border-black">
                                <td class="px-6 py-5 font-bold text-lg uppercase">3. Resultado líquido no mês (1 - 2)</td>
                                <td class="px-6 py-5 font-bold text-lg text-right {{ $lucroLiquido >= 0 ? 'text-green-400' : 'text-red-400' }}">R$ {{ $fmt($lucroLiquido) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    @if($transferenciasInternas > 0)
                        <p class="px-6 py-3 text-[11px] text-gray-400 border-t border-gray-100">
                            R$ {{ $fmt($transferenciasInternas) }} em transferências internas / aplicações ficaram fora do DRE (não são receita nem despesa).
                        </p>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
