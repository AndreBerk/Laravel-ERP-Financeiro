<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatorio_Financeiro_{{ $mesInput }}_{{ $ano }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @php
        $fmt = fn($v) => number_format((float) $v, 2, ',', '.');
        $mesesCurtos = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    @endphp
    <style>
        @media print {
            @if($isAnual)
                @page { size: A4 landscape; margin: 5mm; }
            @else
                @page { size: A4; margin: 10mm; }
            @endif
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        body { padding: @if($isAnual) 5mm @else 15mm @endif; }

        .tabela-anual th, .tabela-anual td {
            text-align: right; font-size: 9px; padding: 5px 2px; border: 1px solid #e5e7eb; white-space: nowrap;
        }
        .tabela-anual td:first-child, .tabela-anual th:first-child {
            text-align: left; font-weight: bold; background-color: #f9fafb; padding-left: 4px;
        }
    </style>
</head>
<body class="bg-white text-gray-900 font-sans max-w-full mx-auto" onload="window.print()">

    <div class="border-b-2 border-black pb-2 mb-4 flex justify-between items-end">
        <div>
            <h1 class="text-lg font-black text-black uppercase tracking-tight">Relatório de Fluxo de Caixa e Auditoria Bancária</h1>
            <h2 class="text-xs font-bold text-gray-600 mt-1">
                @if($isAnual) Resumo Anual Comparativo @else DRE Gerencial Mensal @endif
            </h2>
        </div>
        <div class="text-right">
            <p class="text-[9px] text-gray-500 font-bold uppercase mb-1">Período de referência</p>
            <p class="text-sm font-black text-black bg-gray-100 px-2 py-1 rounded border border-gray-300">
                {{ $isAnual ? 'ANO TODO' : str_pad($mes, 2, '0', STR_PAD_LEFT) }} / {{ $ano }}
            </p>
        </div>
    </div>

    @if($isAnual)
        @php
            $m12 = $mesesData[12];
            $secoes = [
                ['titulo' => 'Painel Comercial: Maquininha (informativo)', 'cabecalho' => 'bg-purple-100 text-purple-900', 'linhas' => [
                    ['Vendas no mês (bruto)', 'vendasBrutas', $totais['vendasBrutas'], 'text-gray-700', false],
                    ['Recebido no mês (bruto)', 'recebimentosBrutos', $totais['recebimentosBrutos'], 'text-gray-700', false],
                    ['Taxas retidas (-)', 'taxasMaquininha', $totais['taxasMaquininha'], 'text-red-700', false],
                    ['A receber no fim do mês (bruto)', 'aReceberBruto', $m12['aReceberBruto'], 'text-purple-800 font-bold', false],
                    ['A receber estimado (líquido)', 'aReceberLiquido', $m12['aReceberLiquido'], 'text-purple-800 font-bold', false],
                ]],
                ['titulo' => 'Dinheiro novo e resultado (extratos bancários)', 'cabecalho' => 'bg-green-100 text-green-900', 'linhas' => [
                    ['1. Dinheiro novo (entradas)', 'receitaCaixa', $totais['receitaCaixa'], 'text-green-800 font-bold', false],
                    ['   Maquininha depositada', 'maquininhaNoBanco', $totais['maquininhaNoBanco'], 'text-gray-700', false],
                    ['   PIX e outros', 'pixOutros', $totais['pixOutros'], 'text-gray-700', false],
                    ['2. Saídas (despesas) (-)', 'saidasBanco', $totais['saidasBanco'], 'text-red-800', false],
                    ['3. RESULTADO LÍQUIDO', 'lucroLiquido', $totais['lucroLiquido'], 'bg-black text-white font-black', false],
                ]],
                ['titulo' => 'Saldo total em conta (Bradesco + Itaú)', 'cabecalho' => 'bg-gray-300 text-gray-900', 'linhas' => [
                    ['Saldo inicial', 'saldoInicialTotal', null, 'text-gray-500', false],
                    ['Entradas (+)', 'entradasTotalContas', $totais['entradasTotalContas'], 'text-green-800', false],
                    ['Saídas (-)', 'saidasTotalContas', $totais['saidasTotalContas'], 'text-red-800', false],
                    ['Saldo final do mês', 'saldoFinalTotal', $m12['saldoFinalTotal'], 'text-black font-black', false],
                ]],
                ['titulo' => 'Controle bancário: Bradesco', 'cabecalho' => 'bg-blue-100 text-blue-900', 'linhas' => [
                    ['Saldo inicial', 'saldoInicialBradesco', null, 'text-gray-500', false],
                    ['Entradas (+)', 'entradasBradesco', $totais['entradasBradesco'], 'text-gray-800', false],
                    ['Saídas (-)', 'saidasBradesco', $totais['saidasBradesco'], 'text-gray-800', false],
                    ['Saldo final do mês', 'saldoFinalBradesco', $m12['saldoFinalBradesco'], 'text-blue-900 font-bold', false],
                ]],
                ['titulo' => 'Controle bancário: Itaú', 'cabecalho' => 'bg-orange-100 text-orange-900', 'linhas' => [
                    ['Saldo inicial', 'saldoInicialItau', null, 'text-gray-500', false],
                    ['Entradas (+)', 'entradasItau', $totais['entradasItau'], 'text-gray-800', false],
                    ['Saídas (-)', 'saidasItau', $totais['saidasItau'], 'text-gray-800', false],
                    ['Saldo final do mês', 'saldoFinalItau', $m12['saldoFinalItau'], 'text-orange-900 font-bold', false],
                ]],
            ];
        @endphp

        <table class="w-full tabela-anual mb-4">
            <thead class="bg-gray-200 text-gray-800 uppercase font-bold text-[8px]">
                <tr>
                    <th>Indicador</th>
                    @foreach($mesesCurtos as $nome) <th>{{ $nome }}</th> @endforeach
                    <th class="bg-gray-300">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($secoes as $secao)
                    <tr><td colspan="14" class="{{ $secao['cabecalho'] }} text-center font-black text-[9px] uppercase py-1">{{ $secao['titulo'] }}</td></tr>
                    @foreach($secao['linhas'] as [$rotulo, $chave, $total, $classe, $x])
                        <tr class="{{ $classe }}">
                            <td class="text-black">{{ $rotulo }}</td>
                            @for($m = 1; $m <= 12; $m++) <td>{{ $fmt($mesesData[$m][$chave]) }}</td> @endfor
                            <td class="bg-gray-100 font-bold text-black">{{ $total === null ? '-' : $fmt($total) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <p class="text-[8px] text-gray-500">Dinheiro novo = entradas reais nos extratos (PIX + depósitos da maquininha), sem saldo anterior e sem transferências entre contas próprias.</p>

    @else
        <!-- INDICADORES PRINCIPAIS -->
        <div class="grid grid-cols-4 gap-3 mb-4">
            <div class="border-2 border-black rounded p-2 bg-gray-100">
                <p class="text-[8px] text-gray-600 uppercase font-bold">Saldo total em conta</p>
                <p class="text-sm font-black">R$ {{ $fmt($saldoFinalTotal) }}</p>
            </div>
            <div class="border border-gray-400 rounded p-2">
                <p class="text-[8px] text-green-700 uppercase font-bold">Dinheiro novo no mês</p>
                <p class="text-sm font-black text-green-800">R$ {{ $fmt($receitaCaixa) }}</p>
            </div>
            <div class="border border-gray-400 rounded p-2">
                <p class="text-[8px] text-purple-700 uppercase font-bold">A receber (maquininha, líq. est.)</p>
                <p class="text-sm font-black text-purple-800">R$ {{ $fmt($aReceberLiquido) }}</p>
            </div>
            <div class="border border-gray-400 rounded p-2">
                <p class="text-[8px] text-blue-700 uppercase font-bold">Previsto total do mês</p>
                <p class="text-sm font-black text-blue-800">R$ {{ $fmt($previstoTotal) }}</p>
            </div>
        </div>

        <!-- MAQUININHA -->
        <div class="mb-4 bg-gray-50 border border-gray-300 rounded overflow-hidden">
            <div class="bg-gray-200 px-3 py-1.5 border-b border-gray-300">
                <h3 class="text-[10px] font-bold text-gray-700 uppercase">Maquininha: previsto x recebido (referência)</h3>
            </div>
            <div class="grid grid-cols-5 divide-x divide-gray-300">
                <div class="p-2 text-center"><p class="text-[8px] text-gray-500 uppercase">Vendas mês (bruto)</p><p class="text-xs font-bold">R$ {{ $fmt($vendasBrutas) }}</p></div>
                <div class="p-2 text-center"><p class="text-[8px] text-gray-500 uppercase">Recebido (bruto)</p><p class="text-xs font-bold">R$ {{ $fmt($recebimentosBrutos) }}</p></div>
                <div class="p-2 text-center"><p class="text-[8px] text-gray-500 uppercase">Taxas retidas</p><p class="text-xs font-bold text-red-600">R$ {{ $fmt($taxasMaquininha) }}</p></div>
                <div class="p-2 text-center"><p class="text-[8px] text-gray-500 uppercase">Líquido recebido</p><p class="text-xs font-bold text-green-700">R$ {{ $fmt($recebimentosLiquidos) }}</p></div>
                <div class="p-2 text-center bg-gray-100"><p class="text-[8px] text-gray-600 uppercase font-bold">A receber (bruto)</p><p class="text-xs font-bold text-purple-700">R$ {{ $fmt($aReceberBruto) }}</p></div>
            </div>
        </div>

        <!-- CONTAS -->
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="border border-gray-400 p-3 rounded">
                <p class="text-[10px] font-black uppercase border-b border-gray-400 mb-2 pb-1 text-blue-900">Extrato Bradesco</p>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-gray-600">Saldo abertura</span> <span>R$ {{ $fmt($saldoInicialBradesco) }}</span></div>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-green-700">Entradas (+)</span> <span class="font-bold">R$ {{ $fmt($entradasBradesco) }}</span></div>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-red-700">Saídas (-)</span> <span class="font-bold">R$ {{ $fmt($saidasBradesco) }}</span></div>
                <div class="flex justify-between text-[12px] font-black mt-2 pt-1 border-t border-gray-400 text-blue-900"><span>Saldo final</span> <span>R$ {{ $fmt($saldoFinalBradesco) }}</span></div>
            </div>
            <div class="border border-gray-400 p-3 rounded">
                <p class="text-[10px] font-black uppercase border-b border-gray-400 mb-2 pb-1 text-orange-900">Extrato Itaú</p>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-gray-600">Saldo abertura</span> <span>R$ {{ $fmt($saldoInicialItau) }}</span></div>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-green-700">Entradas (+)</span> <span class="font-bold">R$ {{ $fmt($entradasItau) }}</span></div>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-red-700">Saídas (-)</span> <span class="font-bold">R$ {{ $fmt($saidasItau) }}</span></div>
                <div class="flex justify-between text-[12px] font-black mt-2 pt-1 border-t border-gray-400 text-orange-900"><span>Saldo final</span> <span>R$ {{ $fmt($saldoFinalItau) }}</span></div>
            </div>
            <div class="border-2 border-black p-3 rounded bg-gray-100">
                <p class="text-[10px] font-black uppercase border-b border-gray-400 mb-2 pb-1">Total das contas</p>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-gray-600">Saldo abertura</span> <span>R$ {{ $fmt($saldoInicialTotal) }}</span></div>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-green-700">Entradas (+)</span> <span class="font-bold">R$ {{ $fmt($entradasTotalContas) }}</span></div>
                <div class="flex justify-between text-[11px] mb-1"><span class="text-red-700">Saídas (-)</span> <span class="font-bold">R$ {{ $fmt($saidasTotalContas) }}</span></div>
                <div class="flex justify-between text-[12px] font-black mt-2 pt-1 border-t border-gray-400"><span>Saldo final</span> <span>R$ {{ $fmt($saldoFinalTotal) }}</span></div>
            </div>
        </div>

        <!-- DRE -->
        <div class="border border-gray-400 rounded overflow-hidden">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-200 border-b border-gray-400">
                    <tr><th class="px-4 py-2 font-bold uppercase text-black">Estrutura de caixa unificada</th><th class="px-4 py-2 font-bold uppercase text-black text-right">Valores</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-300">
                    <tr class="bg-gray-100 border-t-2 border-gray-400">
                        <td class="px-4 py-2 font-bold text-black">1. DINHEIRO NOVO (BRADESCO + ITAÚ)</td>
                        <td class="px-4 py-2 font-bold text-black text-right">R$ {{ $fmt($receitaCaixa) }}</td>
                    </tr>
                    <tr><td class="px-6 py-1 text-gray-700">- Maquininha depositada</td><td class="px-4 py-1 text-gray-600 text-right">R$ {{ $fmt($maquininhaNoBanco) }}</td></tr>
                    <tr><td class="px-6 py-1 text-gray-700">- PIX e outras entradas</td><td class="px-4 py-1 text-gray-600 text-right">R$ {{ $fmt($pixOutros) }}</td></tr>

                    <tr class="bg-gray-100 border-t border-gray-300">
                        <td class="px-4 py-2 font-bold text-gray-800">2. SAÍDAS DE CAIXA (CATEGORIAS)</td>
                        <td class="px-4 py-2 font-bold text-gray-800 text-right">- R$ {{ $fmt($saidasBanco) }}</td>
                    </tr>
                    @forelse($categoriasDespesas as $despesa)
                        <tr>
                            <td class="px-6 py-1.5 text-gray-700 font-medium">- {{ $despesa->categoria_nome }}</td>
                            <td class="px-4 py-1.5 text-gray-600 text-right">R$ {{ $fmt($despesa->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-2 text-gray-400 text-[10px] italic">Nenhuma despesa registrada.</td></tr>
                    @endforelse

                    <tr class="bg-black text-white border-t-2 border-black">
                        <td class="px-4 py-3 font-black uppercase">3. Resultado líquido geral (1 - 2)</td>
                        <td class="px-4 py-3 font-black text-base text-right">R$ {{ $fmt($lucroLiquido) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-[8px] text-gray-500">Dinheiro novo = entradas reais nos extratos (PIX + depósitos da maquininha), sem saldo anterior e sem transferências entre contas próprias.</p>
    @endif

    <div class="mt-4 text-[9px] text-gray-400 flex justify-between">
        <p>Documento gerado em {{ \Carbon\Carbon::now()->format('d/m/Y \à\s H:i') }}</p>
    </div>
    <div class="mt-4 text-center no-print">
        <button onclick="window.close()" class="text-gray-500 underline text-xs font-bold">Fechar</button>
    </div>
</body>
</html>
