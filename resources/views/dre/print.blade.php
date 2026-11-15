<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatorio_Financeiro_{{ $mesInput }}_{{ $ano }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @php
        $fmt = fn($v) => number_format((float) $v, 2, ',', '.');
        $pct = fn($v) => number_format((float) $v, 1, ',', '.') . '%';
        $nomesMeses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $mesesCurtos = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    @endphp
    <style>
        @media print {
            @if($isAnual)
                @page { size: A4 landscape; margin: 6mm; }
            @else
                @page { size: A4; margin: 10mm; }
            @endif
            body { padding: 0 !important; }
            .no-print { display: none !important; }
        }
        body { padding: @if($isAnual) 6mm @else 12mm @endif; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-variant-numeric: tabular-nums; }

        /* Nada de cortar card, linha ou bloco no meio entre páginas */
        .evitar-quebra, tr, .glossario { page-break-inside: avoid; break-inside: avoid; }
        thead { display: table-header-group; }
        h1, h2, h3 { page-break-after: avoid; break-after: avoid; }

        .tabela-anual { border-collapse: collapse; width: 100%; }
        .tabela-anual th, .tabela-anual td {
            text-align: right; font-size: 8.5px; padding: 4px 3px; border-bottom: 1px solid #e2e8f0; white-space: nowrap;
        }
        .tabela-anual td:first-child, .tabela-anual th:first-child { text-align: left; padding-left: 6px; }
    </style>
</head>
<body class="bg-white text-slate-900 font-sans max-w-full mx-auto" onload="window.print()">

    <!-- CABEÇALHO -->
    <header class="evitar-quebra border-b-2 border-slate-900 pb-2 mb-4 flex justify-between items-end">
        <div>
            <p class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Regime de caixa &middot; extratos Bradesco + Itaú</p>
            <h1 class="text-lg font-black text-slate-900 tracking-tight">Relatório de Fluxo de Caixa (DRE gerencial)</h1>
            <p class="text-[10px] font-semibold text-slate-600">
                @if($isAnual) Resumo anual comparativo, mês a mês @else Demonstrativo mensal @endif
            </p>
        </div>
        <div class="text-right">
            <p class="text-[8px] text-slate-500 font-bold uppercase mb-1">Período</p>
            <p class="text-sm font-black text-slate-900 bg-slate-100 px-3 py-1 rounded border border-slate-300">
                {{ $isAnual ? "Ano {$ano}" : $nomesMeses[$mes] . '/' . $ano }}
            </p>
        </div>
    </header>

    @if($isAnual)
        @php
            $m12 = $mesesData[12];
            $secoes = [
                ['titulo' => 'Dinheiro novo e resultado (extratos bancários)', 'cabecalho' => 'bg-emerald-50 text-emerald-900 border-l-emerald-500', 'linhas' => [
                    ['1. Dinheiro novo (entradas)', 'receitaCaixa', $totais['receitaCaixa'], 'text-emerald-800 font-bold', false],
                    ['    Maquininha depositada', 'maquininhaNoBanco', $totais['maquininhaNoBanco'], 'text-slate-600', false],
                    ['    PIX e outros', 'pixOutros', $totais['pixOutros'], 'text-slate-600', false],
                    ['2. Despesas operacionais (−)', 'saidasBanco', $totais['saidasBanco'], 'text-rose-800', false],
                    ['3. Resultado operacional', 'resultadoOperacional', $totais['resultadoOperacional'], 'bg-slate-800 text-white font-black', true],
                ]],
                ['titulo' => 'Destinação aos potes (não é despesa)', 'cabecalho' => 'bg-amber-50 text-amber-900 border-l-amber-500', 'linhas' => [
                    ['    Lucro distribuído aos sócios', 'destLucro', $totais['destLucro'], 'text-slate-700', false],
                    ['    Reinvestimento no negócio', 'destReinvestimento', $totais['destReinvestimento'], 'text-slate-700', false],
                    ['    Reserva / investimento (líq.)', 'destInvestimento', $totais['destInvestimento'], 'text-slate-700', false],
                    ['4. Total destinado (−)', 'totalDestinacoes', $totais['totalDestinacoes'], 'text-amber-900 font-bold', false],
                    ['5. Resultado líquido de caixa', 'lucroLiquido', $totais['lucroLiquido'], 'bg-black text-white font-black', true],
                ]],
                ['titulo' => 'Maquininha (informativo)', 'cabecalho' => 'bg-violet-50 text-violet-900 border-l-violet-500', 'linhas' => [
                    ['Vendas no mês (bruto)', 'vendasBrutas', $totais['vendasBrutas'], 'text-slate-700', false],
                    ['Recebido no mês (bruto)', 'recebimentosBrutos', $totais['recebimentosBrutos'], 'text-slate-700', false],
                    ['Taxas retidas (−)', 'taxasMaquininha', $totais['taxasMaquininha'], 'text-rose-700', false],
                    ['A receber no fim do mês (bruto)', 'aReceberBruto', $m12['aReceberBruto'], 'text-violet-800 font-bold', false],
                    ['A receber estimado (líquido)', 'aReceberLiquido', $m12['aReceberLiquido'], 'text-violet-800 font-bold', false],
                ]],
                ['titulo' => 'Saldo total em conta (Bradesco + Itaú)', 'cabecalho' => 'bg-slate-100 text-slate-900 border-l-slate-500', 'linhas' => [
                    ['Saldo inicial', 'saldoInicialTotal', null, 'text-slate-500', false],
                    ['Entradas (+)', 'entradasTotalContas', $totais['entradasTotalContas'], 'text-emerald-800', false],
                    ['Saídas (−)', 'saidasTotalContas', $totais['saidasTotalContas'], 'text-rose-800', false],
                    ['Saldo final do mês', 'saldoFinalTotal', $m12['saldoFinalTotal'], 'text-slate-900 font-black', false],
                ]],
                ['titulo' => 'Conta Bradesco', 'cabecalho' => 'bg-blue-50 text-blue-900 border-l-blue-500', 'linhas' => [
                    ['Saldo inicial', 'saldoInicialBradesco', null, 'text-slate-500', false],
                    ['Entradas (+)', 'entradasBradesco', $totais['entradasBradesco'], 'text-slate-800', false],
                    ['Saídas (−)', 'saidasBradesco', $totais['saidasBradesco'], 'text-slate-800', false],
                    ['Saldo final do mês', 'saldoFinalBradesco', $m12['saldoFinalBradesco'], 'text-blue-900 font-bold', false],
                ]],
                ['titulo' => 'Conta Itaú', 'cabecalho' => 'bg-orange-50 text-orange-900 border-l-orange-500', 'linhas' => [
                    ['Saldo inicial', 'saldoInicialItau', null, 'text-slate-500', false],
                    ['Entradas (+)', 'entradasItau', $totais['entradasItau'], 'text-slate-800', false],
                    ['Saídas (−)', 'saidasItau', $totais['saidasItau'], 'text-slate-800', false],
                    ['Saldo final do mês', 'saldoFinalItau', $m12['saldoFinalItau'], 'text-orange-900 font-bold', false],
                ]],
            ];
        @endphp

        <!-- INDICADORES DO ANO -->
        <div class="evitar-quebra grid grid-cols-4 gap-2 mb-3">
            @foreach([
                ['Dinheiro novo no ano', $totais['receitaCaixa'], 'border-emerald-500 text-emerald-800', 'entrou de fato nos bancos'],
                ['Resultado operacional', $totais['resultadoOperacional'], 'border-sky-500 text-sky-800', 'margem ' . $pct($totais['margemOperacional'])],
                ['Destinado aos potes', $totais['totalDestinacoes'], 'border-amber-500 text-amber-800', 'não é despesa'],
                ['Maquininha a receber (líq.)', $m12['aReceberLiquido'], 'border-violet-500 text-violet-800', 'estimado em dezembro'],
            ] as [$rot, $v, $tom, $sub])
                <div class="border-l-4 {{ $tom }} bg-slate-50 rounded-r px-2.5 py-1.5">
                    <p class="text-[8px] font-bold uppercase">{{ $rot }}</p>
                    <p class="text-sm font-black text-slate-900">R$ {{ $fmt($v) }}</p>
                    <p class="text-[7.5px] text-slate-500">{{ $sub }}</p>
                </div>
            @endforeach
        </div>

        <table class="tabela-anual mb-2">
            <thead class="bg-slate-100 text-slate-600 uppercase font-bold text-[8px]">
                <tr>
                    <th>Indicador</th>
                    @foreach($mesesCurtos as $nome) <th>{{ $nome }}</th> @endforeach
                    <th class="bg-slate-200 text-slate-900">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($secoes as $secao)
                    <tr><td colspan="14" class="{{ $secao['cabecalho'] }} border-l-4 !text-left font-black text-[8.5px] uppercase tracking-wide py-1">{{ $secao['titulo'] }}</td></tr>
                    @foreach($secao['linhas'] as [$rotulo, $chave, $total, $classe, $resultado])
                        <tr class="{{ $classe }}">
                            <td class="{{ $resultado ? '' : 'text-slate-900 font-semibold' }} whitespace-pre">{{ $rotulo }}</td>
                            @for($m = 1; $m <= 12; $m++) <td>{{ $fmt($mesesData[$m][$chave]) }}</td> @endfor
                            <td class="{{ $resultado ? '' : 'bg-slate-100 text-slate-900' }} font-bold">{{ $total === null ? '–' : $fmt($total) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <p class="text-[8px] text-slate-500">Leitura em cascata: 1 − 2 = 3 (o que a loja gerou) e 3 − 4 = 5 (o que ficou livre depois de separar os potes). Margem líquida no ano: {{ $pct($totais['margemLucro']) }}.</p>

    @else
        <!-- INDICADORES PRINCIPAIS -->
        <div class="evitar-quebra grid grid-cols-3 gap-2 mb-2">
            <div class="col-span-2 rounded bg-slate-900 text-white px-3 py-2 flex justify-between items-end">
                <div>
                    <p class="text-[8px] uppercase font-bold text-slate-400">Saldo total em conta (fim do mês)</p>
                    <p class="text-lg font-black">R$ {{ $fmt($saldoFinalTotal) }}</p>
                </div>
                <p class="text-[8.5px] text-slate-300">Bradesco R$ {{ $fmt($saldoFinalBradesco) }} + Itaú R$ {{ $fmt($saldoFinalItau) }}</p>
            </div>
            <div class="rounded border border-slate-300 px-3 py-2">
                <p class="text-[8px] uppercase font-bold text-slate-500">Previsto total do mês</p>
                <p class="text-base font-black text-sky-800">R$ {{ $fmt($previstoTotal) }}</p>
                <p class="text-[7.5px] text-slate-500">dinheiro novo + a receber</p>
            </div>
        </div>
        <div class="evitar-quebra grid grid-cols-4 gap-2 mb-4">
            @foreach([
                ['Dinheiro novo', $receitaCaixa, 'border-emerald-500 text-emerald-800', 'pingou na conta no mês'],
                ['Resultado operacional', $resultadoOperacional, 'border-sky-500 text-sky-800', 'margem ' . $pct($margemOperacional)],
                ['Destinado aos potes', $totalDestinacoes, 'border-amber-500 text-amber-800', 'não é despesa'],
                ['Maquininha a receber', $aReceberLiquido, 'border-violet-500 text-violet-800', 'líquido estimado'],
            ] as [$rot, $v, $tom, $sub])
                <div class="border-l-4 {{ $tom }} bg-slate-50 rounded-r px-2.5 py-1.5">
                    <p class="text-[8px] font-bold uppercase">{{ $rot }}</p>
                    <p class="text-sm font-black text-slate-900">R$ {{ $fmt($v) }}</p>
                    <p class="text-[7.5px] text-slate-500">{{ $sub }}</p>
                </div>
            @endforeach
        </div>

        <!-- DEMONSTRATIVO -->
        <div class="border border-slate-300 rounded overflow-hidden mb-4">
            <table class="w-full text-[11px] text-left">
                <thead class="bg-slate-100 border-b border-slate-300">
                    <tr><th class="px-4 py-1.5 font-bold uppercase text-[9px] text-slate-700">Demonstrativo de caixa: {{ $nomesMeses[$mes] }}/{{ $ano }}</th><th class="px-4 py-1.5 font-bold uppercase text-[9px] text-slate-700 text-right">Valores</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr class="bg-emerald-50">
                        <td class="px-4 py-2 font-bold text-emerald-900 border-l-4 border-emerald-500">1. Dinheiro novo (Bradesco + Itaú)</td>
                        <td class="px-4 py-2 font-bold text-emerald-900 text-right">R$ {{ $fmt($receitaCaixa) }}</td>
                    </tr>
                    <tr><td class="pl-8 pr-4 py-1 text-slate-600">Maquininha depositada</td><td class="px-4 py-1 text-slate-600 text-right">R$ {{ $fmt($maquininhaNoBanco) }}</td></tr>
                    <tr><td class="pl-8 pr-4 py-1 text-slate-600">PIX e outras entradas</td><td class="px-4 py-1 text-slate-600 text-right">R$ {{ $fmt($pixOutros) }}</td></tr>

                    <tr class="bg-rose-50">
                        <td class="px-4 py-2 font-bold text-rose-900 border-l-4 border-rose-500">2. Despesas operacionais (por categoria)</td>
                        <td class="px-4 py-2 font-bold text-rose-900 text-right">− R$ {{ $fmt($saidasBanco) }}</td>
                    </tr>
                    @forelse($categoriasDespesas as $despesa)
                        <tr>
                            <td class="pl-8 pr-4 py-1 text-slate-600">{{ $despesa->categoria_nome }}</td>
                            <td class="px-4 py-1 text-slate-600 text-right">R$ {{ $fmt($despesa->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="pl-8 pr-4 py-1.5 text-slate-400 text-[9px] italic">Nenhuma despesa registrada.</td></tr>
                    @endforelse

                    <tr class="bg-slate-800 text-white">
                        <td class="px-4 py-2 font-black">3. Resultado operacional (1 − 2) &middot; margem {{ $pct($margemOperacional) }}</td>
                        <td class="px-4 py-2 font-black text-right">R$ {{ $fmt($resultadoOperacional) }}</td>
                    </tr>
                    <tr class="bg-amber-50">
                        <td class="px-4 py-2 font-bold text-amber-900 border-l-4 border-amber-500">4. Destinação aos potes <span class="text-[8px] font-bold uppercase bg-amber-200 rounded px-1 ml-1">não é despesa</span></td>
                        <td class="px-4 py-2 font-bold text-amber-900 text-right">− R$ {{ $fmt($totalDestinacoes) }}</td>
                    </tr>
                    <tr><td class="pl-8 pr-4 py-1 text-slate-600">Lucro distribuído aos sócios</td><td class="px-4 py-1 text-slate-600 text-right">R$ {{ $fmt($destLucro) }}</td></tr>
                    <tr><td class="pl-8 pr-4 py-1 text-slate-600">Reinvestimento no negócio</td><td class="px-4 py-1 text-slate-600 text-right">R$ {{ $fmt($destReinvestimento) }}</td></tr>
                    <tr><td class="pl-8 pr-4 py-1 text-slate-600">Reserva / investimento (aplicações − resgates)</td><td class="px-4 py-1 text-slate-600 text-right">R$ {{ $fmt($destInvestimento) }}</td></tr>

                    <tr class="bg-black text-white">
                        <td class="px-4 py-2.5 font-black">5. Resultado líquido de caixa (3 − 4) &middot; margem {{ $pct($margemLucro) }}</td>
                        <td class="px-4 py-2.5 font-black text-sm text-right">R$ {{ $fmt($lucroLiquido) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MAQUININHA -->
        <div class="evitar-quebra mb-4 border border-slate-300 rounded overflow-hidden">
            <div class="bg-violet-50 px-3 py-1.5 border-b border-slate-300 border-l-4 border-l-violet-500">
                <h3 class="text-[9px] font-bold text-violet-900 uppercase">Maquininha: vendido x recebido (informativo)</h3>
            </div>
            <div class="grid grid-cols-5 divide-x divide-slate-200">
                <div class="p-2 text-center"><p class="text-[8px] text-slate-500 uppercase">Vendas (bruto)</p><p class="text-xs font-bold">R$ {{ $fmt($vendasBrutas) }}</p></div>
                <div class="p-2 text-center"><p class="text-[8px] text-slate-500 uppercase">Recebido (bruto)</p><p class="text-xs font-bold">R$ {{ $fmt($recebimentosBrutos) }}</p></div>
                <div class="p-2 text-center"><p class="text-[8px] text-slate-500 uppercase">Taxas retidas</p><p class="text-xs font-bold text-rose-700">R$ {{ $fmt($taxasMaquininha) }}</p></div>
                <div class="p-2 text-center"><p class="text-[8px] text-slate-500 uppercase">Líquido recebido</p><p class="text-xs font-bold text-emerald-700">R$ {{ $fmt($recebimentosLiquidos) }}</p></div>
                <div class="p-2 text-center bg-violet-50"><p class="text-[8px] text-violet-800 uppercase font-bold">A receber (bruto)</p><p class="text-xs font-bold text-violet-800">R$ {{ $fmt($aReceberBruto) }}</p></div>
            </div>
        </div>

        <!-- CONTAS -->
        <div class="evitar-quebra grid grid-cols-3 gap-2">
            @foreach([
                ['Extrato Bradesco', 'border-t-blue-600 text-blue-900', $saldoInicialBradesco, $entradasBradesco, $saidasBradesco, $saldoFinalBradesco],
                ['Extrato Itaú', 'border-t-orange-600 text-orange-900', $saldoInicialItau, $entradasItau, $saidasItau, $saldoFinalItau],
                ['Total das contas', 'border-t-slate-900 text-slate-900', $saldoInicialTotal, $entradasTotalContas, $saidasTotalContas, $saldoFinalTotal],
            ] as [$nome, $tom, $ini, $ent, $sai, $fim])
                <div class="border border-slate-300 border-t-4 {{ $tom }} p-2.5 rounded">
                    <p class="text-[9px] font-black uppercase mb-1.5">{{ $nome }}</p>
                    <div class="flex justify-between text-[10px] text-slate-600"><span>Saldo abertura</span><span>R$ {{ $fmt($ini) }}</span></div>
                    <div class="flex justify-between text-[10px] text-emerald-800"><span>Entradas (+)</span><span class="font-bold">R$ {{ $fmt($ent) }}</span></div>
                    <div class="flex justify-between text-[10px] text-rose-800"><span>Saídas (−)</span><span class="font-bold">R$ {{ $fmt($sai) }}</span></div>
                    <div class="flex justify-between text-[11px] font-black mt-1 pt-1 border-t border-slate-300"><span>Saldo final</span><span>R$ {{ $fmt($fim) }}</span></div>
                </div>
            @endforeach
        </div>
        @if($transferenciasInternas > 0)
            <p class="mt-2 text-[8px] text-slate-500">R$ {{ $fmt($transferenciasInternas) }} em transferências internas / aplicações ficaram fora do DRE (não são receita nem despesa).</p>
        @endif
    @endif

    @include('dre.partials.glossario', ['modo' => 'print'])

    <footer class="mt-3 text-[8px] text-slate-400 flex justify-between">
        <p>Documento gerado em {{ \Carbon\Carbon::now()->format('d/m/Y \à\s H:i') }}</p>
        <p>Valores em R$ &middot; regime de caixa</p>
    </footer>
    <div class="mt-4 text-center no-print">
        <button onclick="window.close()" class="text-slate-500 underline text-xs font-bold">Fechar</button>
    </div>
</body>
</html>
