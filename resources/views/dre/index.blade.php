<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Demonstrativo de Resultados (DRE)') }}
        </h2>
    </x-slot>

    @php
        $fmt = fn($v) => number_format((float) $v, 2, ',', '.');
        $pct = fn($v) => number_format((float) $v, 1, ',', '.') . '%';
        $nomesMeses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $mesesCurtos = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $card = 'bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm';
    @endphp

    <style>
        .tabela-anual { font-variant-numeric: tabular-nums; }
        .tabela-anual th, .tabela-anual td { padding: 7px 8px; font-size: 11.5px; text-align: right; white-space: nowrap; }
        .tabela-anual td:first-child, .tabela-anual th:first-child { text-align: left; position: sticky; left: 0; z-index: 1; }
        summary::-webkit-details-marker { display: none; }
    </style>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 tabular-nums">

            <!-- CABEÇALHO E FILTROS -->
            <div class="{{ $card }} p-6 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        {{ $isAnual ? 'Visão anual' : 'Visão mensal' }} &middot; regime de caixa
                    </p>
                    <h3 class="text-2xl font-bold text-slate-900 mt-1">
                        {{ $isAnual ? "Resultado de {$ano}" : $nomesMeses[$mes] . ' de ' . $ano }}
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">Fluxo de caixa real dos extratos, destinação aos potes e previsão da maquininha.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('dre.index') }}" class="flex flex-wrap items-center gap-2">
                        <select name="mes" class="rounded-lg border-slate-300 text-sm py-2 focus:border-slate-500 focus:ring-slate-500">
                            <option value="todos" {{ $mesInput === 'todos' ? 'selected' : '' }}>Ano todo (comparativo)</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $mesInput == $i ? 'selected' : '' }}>{{ $nomesMeses[$i] }}</option>
                            @endfor
                        </select>
                        <select name="ano" class="rounded-lg border-slate-300 text-sm py-2 focus:border-slate-500 focus:ring-slate-500">
                            @for ($i = date('Y') - 1; $i <= date('Y') + 2; $i++)
                                <option value="{{ $i }}" {{ $ano == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                        <button type="submit" class="bg-slate-900 hover:bg-slate-700 text-white font-semibold py-2 px-5 rounded-lg transition text-sm">Filtrar</button>
                    </form>
                    <a href="{{ route('dre.imprimir', ['mes' => $mesInput, 'ano' => $ano]) }}" target="_blank"
                       class="inline-flex items-center gap-2 bg-white ring-1 ring-slate-300 hover:bg-slate-100 text-slate-700 font-semibold py-2 px-4 rounded-lg transition text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                        Imprimir / PDF
                    </a>
                </div>
            </div>

            @if($isAnual)
                <!-- ============================================== -->
                <!-- LAYOUT ANUAL -->
                <!-- ============================================== -->
                @php
                    $m12 = $mesesData[12];
                    // [rótulo, chave do mês, total (null = "-"), classe do texto, linha de resultado?]
                    $secoes = [
                        ['titulo' => 'Dinheiro novo e resultado', 'sub' => 'extratos bancários', 'tom' => 'emerald', 'linhas' => [
                            ['1. Dinheiro novo (entradas)', 'receitaCaixa', $totais['receitaCaixa'], 'text-emerald-700 font-bold', false],
                            ['Maquininha depositada', 'maquininhaNoBanco', $totais['maquininhaNoBanco'], 'text-slate-500 pl-6', false],
                            ['PIX e outros', 'pixOutros', $totais['pixOutros'], 'text-slate-500 pl-6', false],
                            ['2. Despesas operacionais (−)', 'saidasBanco', $totais['saidasBanco'], 'text-rose-700 font-semibold', false],
                            ['3. Resultado operacional', 'resultadoOperacional', $totais['resultadoOperacional'], '', true],
                        ]],
                        ['titulo' => 'Destinação aos potes', 'sub' => 'não é despesa', 'tom' => 'amber', 'linhas' => [
                            ['Lucro distribuído aos sócios', 'destLucro', $totais['destLucro'], 'text-slate-600 pl-6', false],
                            ['Reinvestimento no negócio', 'destReinvestimento', $totais['destReinvestimento'], 'text-slate-600 pl-6', false],
                            ['Reserva / investimento (líq.)', 'destInvestimento', $totais['destInvestimento'], 'text-slate-600 pl-6', false],
                            ['4. Total destinado (−)', 'totalDestinacoes', $totais['totalDestinacoes'], 'text-amber-800 font-semibold', false],
                            ['5. Resultado líquido de caixa', 'lucroLiquido', $totais['lucroLiquido'], '', true],
                        ]],
                        ['titulo' => 'Maquininha', 'sub' => 'informativo, não altera o resultado', 'tom' => 'violet', 'linhas' => [
                            ['Vendas no mês (bruto)', 'vendasBrutas', $totais['vendasBrutas'], 'text-slate-600', false],
                            ['Recebido no mês (bruto)', 'recebimentosBrutos', $totais['recebimentosBrutos'], 'text-slate-600', false],
                            ['Taxas retidas (−)', 'taxasMaquininha', $totais['taxasMaquininha'], 'text-rose-600', false],
                            ['A receber no fim do mês (bruto)', 'aReceberBruto', $m12['aReceberBruto'], 'text-violet-700 font-semibold', false],
                            ['A receber estimado (líquido)', 'aReceberLiquido', $m12['aReceberLiquido'], 'text-violet-700 font-semibold', false],
                        ]],
                        ['titulo' => 'Saldo total em conta', 'sub' => 'Bradesco + Itaú', 'tom' => 'slate', 'linhas' => [
                            ['Saldo inicial', 'saldoInicialTotal', null, 'text-slate-500', false],
                            ['Entradas (+)', 'entradasTotalContas', $totais['entradasTotalContas'], 'text-emerald-700', false],
                            ['Saídas (−)', 'saidasTotalContas', $totais['saidasTotalContas'], 'text-rose-600', false],
                            ['Saldo final do mês', 'saldoFinalTotal', $m12['saldoFinalTotal'], 'text-slate-900 font-bold', false],
                        ]],
                        ['titulo' => 'Conta Bradesco', 'sub' => 'fluxo de caixa', 'tom' => 'blue', 'linhas' => [
                            ['Saldo inicial', 'saldoInicialBradesco', null, 'text-slate-500', false],
                            ['Entradas (+)', 'entradasBradesco', $totais['entradasBradesco'], 'text-emerald-700', false],
                            ['Saídas (−)', 'saidasBradesco', $totais['saidasBradesco'], 'text-rose-600', false],
                            ['Saldo final do mês', 'saldoFinalBradesco', $m12['saldoFinalBradesco'], 'text-blue-900 font-bold', false],
                        ]],
                        ['titulo' => 'Conta Itaú', 'sub' => 'fluxo de caixa', 'tom' => 'orange', 'linhas' => [
                            ['Saldo inicial', 'saldoInicialItau', null, 'text-slate-500', false],
                            ['Entradas (+)', 'entradasItau', $totais['entradasItau'], 'text-emerald-700', false],
                            ['Saídas (−)', 'saidasItau', $totais['saidasItau'], 'text-rose-600', false],
                            ['Saldo final do mês', 'saldoFinalItau', $m12['saldoFinalItau'], 'text-orange-900 font-bold', false],
                        ]],
                    ];
                    $tons = [
                        'emerald' => 'bg-emerald-50 text-emerald-900 border-l-emerald-500',
                        'amber' => 'bg-amber-50 text-amber-900 border-l-amber-500',
                        'violet' => 'bg-violet-50 text-violet-900 border-l-violet-500',
                        'slate' => 'bg-slate-100 text-slate-900 border-l-slate-500',
                        'blue' => 'bg-blue-50 text-blue-900 border-l-blue-500',
                        'orange' => 'bg-orange-50 text-orange-900 border-l-orange-500',
                    ];
                @endphp

                <!-- INDICADORES DO ANO -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="{{ $card }} p-5 border-t-4 border-t-emerald-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Dinheiro novo no ano</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($totais['receitaCaixa']) }}</p>
                        <p class="text-xs text-slate-500 mt-2">O que entrou de fato nos bancos (PIX + maquininha).</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-sky-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Resultado operacional</p>
                        <p class="text-2xl font-bold mt-2 {{ $totais['resultadoOperacional'] >= 0 ? 'text-slate-900' : 'text-rose-600' }}">R$ {{ $fmt($totais['resultadoOperacional']) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Sobra após as despesas &middot; margem {{ $pct($totais['margemOperacional']) }}</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-amber-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Destinado aos potes</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($totais['totalDestinacoes']) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Guardado ou retirado, não é despesa.</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-violet-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Maquininha a receber</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($m12['aReceberLiquido']) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Estimado líquido em dezembro &middot; bruto R$ {{ $fmt($m12['aReceberBruto']) }}</p>
                    </div>
                </div>

                @include('dre.partials.glossario', ['modo' => 'web'])

                <!-- COMPARATIVO MENSAL -->
                <div class="{{ $card }} overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">Comparativo mês a mês: {{ $ano }}</h3>
                        <span class="text-xs text-slate-500">Margem líquida no ano {{ $pct($totais['margemLucro']) }} &middot; role para o lado no celular</span>
                    </div>
                    <div class="w-full overflow-x-auto">
                        <table class="w-full tabela-anual">
                            <thead class="text-[10.5px] uppercase tracking-wide text-slate-500">
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="bg-slate-50 min-w-[220px] font-semibold">Indicador</th>
                                    @foreach($mesesCurtos as $nome) <th class="font-semibold">{{ $nome }}</th> @endforeach
                                    <th class="bg-slate-100 font-bold text-slate-700">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($secoes as $secao)
                                    <tr>
                                        <td colspan="14" class="{{ $tons[$secao['tom']] }} border-l-4 !text-left !py-2 text-xs font-bold uppercase tracking-wide">
                                            {{ $secao['titulo'] }} <span class="ml-1 font-medium normal-case tracking-normal opacity-70">{{ $secao['sub'] }}</span>
                                        </td>
                                    </tr>
                                    @foreach($secao['linhas'] as [$rotulo, $chave, $total, $classe, $resultado])
                                        <tr class="border-b border-slate-100 {{ $resultado ? 'bg-slate-900 text-white' : 'bg-white hover:bg-slate-50 ' . $classe }}">
                                            <td class="{{ $resultado ? 'bg-slate-900 font-bold' : 'bg-white ' . $classe }}">{{ $rotulo }}</td>
                                            @for($m = 1; $m <= 12; $m++)
                                                @php $v = $mesesData[$m][$chave]; @endphp
                                                <td class="{{ $resultado ? ($v < 0 ? 'text-rose-300 font-semibold' : 'text-emerald-300 font-semibold') : ((float) $v == 0.0 ? 'text-slate-300' : '') }}">{{ $fmt($v) }}</td>
                                            @endfor
                                            <td class="{{ $resultado ? 'bg-black font-bold ' . ($total < 0 ? 'text-rose-300' : 'text-emerald-300') : 'bg-slate-50 font-bold text-slate-800' }}">
                                                {{ $total === null ? '–' : $fmt($total) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="px-6 py-3 text-xs text-slate-500 border-t border-slate-100 bg-slate-50/60">
                        Leitura em cascata: <strong class="text-slate-700">1 − 2 = 3</strong> (o que a loja gerou) e <strong class="text-slate-700">3 − 4 = 5</strong> (o que ficou livre depois de separar os potes).
                        "A receber" considera só as vendas deste ano ainda não depositadas.
                    </p>
                </div>

            @else
                <!-- ============================================== -->
                <!-- LAYOUT MENSAL -->
                <!-- ============================================== -->

                <!-- SALDO EM CONTA -->
                <div class="rounded-2xl bg-slate-900 text-white p-6 shadow-sm flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Saldo total em conta no fim do mês</p>
                        <p class="text-4xl font-bold mt-1">R$ {{ $fmt($saldoFinalTotal) }}</p>
                        <p class="text-sm text-slate-400 mt-2">Bradesco R$ {{ $fmt($saldoFinalBradesco) }} &nbsp;+&nbsp; Itaú R$ {{ $fmt($saldoFinalItau) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Previsto total do mês</p>
                        <p class="text-2xl font-bold text-sky-300 mt-1">R$ {{ $fmt($previstoTotal) }}</p>
                        <p class="text-xs text-slate-400 mt-1">Dinheiro novo + maquininha a receber</p>
                    </div>
                </div>

                <!-- MÉTRICAS PRINCIPAIS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="{{ $card }} p-5 border-t-4 border-t-emerald-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Dinheiro novo</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($receitaCaixa) }}</p>
                        <p class="text-xs text-slate-500 mt-2">O que "pingou" na conta neste mês.</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-sky-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Resultado operacional</p>
                        <p class="text-2xl font-bold mt-2 {{ $resultadoOperacional >= 0 ? 'text-slate-900' : 'text-rose-600' }}">R$ {{ $fmt($resultadoOperacional) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Sobra após pagar os custos &middot; margem {{ $pct($margemOperacional) }}</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-amber-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Destinado aos potes</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($totalDestinacoes) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Guardado ou retirado, não é despesa.</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-violet-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Maquininha a receber</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($aReceberLiquido) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Estimado após taxas &middot; bruto R$ {{ $fmt($aReceberBruto) }}</p>
                    </div>
                </div>

                @include('dre.partials.glossario', ['modo' => 'web'])

                <!-- DEMONSTRATIVO EM CASCATA -->
                <div class="{{ $card }} overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap justify-between items-baseline gap-2">
                        <h3 class="text-base font-bold text-slate-900">Demonstrativo de caixa: {{ $nomesMeses[$mes] }}/{{ $ano }}</h3>
                        <span class="text-xs text-slate-500">Margem operacional {{ $pct($margemOperacional) }} &middot; líquida {{ $pct($margemLucro) }}</span>
                    </div>

                    @php
                        $base = max($receitaCaixa, 0.01);
                        $barra = [
                            ['Despesas', 'bg-rose-400', max(0, $saidasBanco)],
                            ['Lucro sócios', 'bg-amber-500', max(0, $destLucro)],
                            ['Reinvestimento', 'bg-sky-500', max(0, $destReinvestimento)],
                            ['Reserva', 'bg-emerald-500', max(0, $destInvestimento)],
                            ['Sobra em caixa', 'bg-slate-300', max(0, $lucroLiquido)],
                        ];
                    @endphp
                    @if($receitaCaixa > 0)
                        <div class="px-6 pt-5">
                            <p class="text-xs font-semibold text-slate-500 mb-2">Para onde foi cada R$ 100 de dinheiro novo</p>
                            <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex">
                                @foreach($barra as [$rot, $cor, $v])
                                    <div class="{{ $cor }} h-3" style="width: {{ number_format(min(100, $v / $base * 100), 2, '.', '') }}%" title="{{ $rot }}: R$ {{ $fmt($v) }}"></div>
                                @endforeach
                            </div>
                            <div class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-xs text-slate-600">
                                @foreach($barra as [$rot, $cor, $v])
                                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full {{ $cor }}"></span>{{ $rot }} <strong class="text-slate-800">{{ number_format($v / $base * 100, 0) }}%</strong></span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <table class="w-full text-sm mt-5">
                        <tbody>
                            <!-- ENTRADAS -->
                            <tr class="bg-emerald-50 border-l-4 border-l-emerald-500">
                                <td class="px-6 py-3.5 font-bold text-emerald-900">1. Dinheiro novo <span class="font-normal text-emerald-700 text-xs">entradas Bradesco + Itaú</span></td>
                                <td class="px-6 py-3.5 font-bold text-emerald-800 text-right">R$ {{ $fmt($receitaCaixa) }}</td>
                            </tr>
                            <tr class="border-b border-slate-100">
                                <td class="pl-12 pr-6 py-2 text-slate-600">Maquininha depositada</td>
                                <td class="px-6 py-2 text-slate-600 text-right">R$ {{ $fmt($maquininhaNoBanco) }}</td>
                            </tr>
                            <tr>
                                <td class="pl-12 pr-6 py-2 text-slate-600">PIX e outras entradas</td>
                                <td class="px-6 py-2 text-slate-600 text-right">R$ {{ $fmt($pixOutros) }}</td>
                            </tr>

                            <!-- SAÍDAS -->
                            <tr class="bg-rose-50 border-l-4 border-l-rose-500">
                                <td class="px-6 py-3.5 font-bold text-rose-900">2. Despesas operacionais <span class="font-normal text-rose-700 text-xs">custos da loja, por categoria</span></td>
                                <td class="px-6 py-3.5 font-bold text-rose-700 text-right">− R$ {{ $fmt($saidasBanco) }}</td>
                            </tr>
                            @forelse($categoriasDespesas as $despesa)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="pl-12 pr-6 py-2 text-slate-600">{{ $despesa->categoria_nome }}</td>
                                    <td class="px-6 py-2 text-slate-600 text-right">R$ {{ $fmt($despesa->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="pl-12 pr-6 py-3 text-slate-400 text-xs italic">Nenhuma despesa registrada neste período.</td></tr>
                            @endforelse

                            <tr class="bg-slate-900 text-white">
                                <td class="px-6 py-4 font-bold">3. Resultado operacional <span class="font-normal text-slate-400 text-xs">(1 − 2) o que a loja gerou</span></td>
                                <td class="px-6 py-4 font-bold text-lg text-right {{ $resultadoOperacional >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">R$ {{ $fmt($resultadoOperacional) }}</td>
                            </tr>

                            <!-- POTES -->
                            <tr class="bg-amber-50 border-l-4 border-l-amber-500">
                                <td class="px-6 py-3.5 font-bold text-amber-900">
                                    4. Destinação aos potes
                                    <span class="ml-1 text-[10px] font-semibold uppercase tracking-wide bg-amber-200/70 text-amber-900 rounded-full px-2 py-0.5">não é despesa</span>
                                </td>
                                <td class="px-6 py-3.5 font-bold text-amber-800 text-right">− R$ {{ $fmt($totalDestinacoes) }}</td>
                            </tr>
                            @foreach([
                                ['Lucro distribuído aos sócios', 'bg-amber-500', $destLucro],
                                ['Reinvestimento no negócio', 'bg-sky-500', $destReinvestimento],
                                ['Reserva / investimento (aplicações − resgates)', 'bg-emerald-500', $destInvestimento],
                            ] as [$rot, $cor, $v])
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="pl-12 pr-6 py-2 text-slate-600"><span class="inline-block w-2 h-2 rounded-full {{ $cor }} mr-2 align-middle"></span>{{ $rot }}</td>
                                    <td class="px-6 py-2 text-slate-600 text-right">R$ {{ $fmt($v) }}</td>
                                </tr>
                            @endforeach

                            <tr class="bg-black text-white">
                                <td class="px-6 py-5 font-bold text-base">5. Resultado líquido de caixa <span class="font-normal text-slate-400 text-xs">(3 − 4) o que ficou livre</span></td>
                                <td class="px-6 py-5 font-bold text-xl text-right {{ $lucroLiquido >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">R$ {{ $fmt($lucroLiquido) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Conferência: extrato x módulo de Potes -->
                    <div class="px-6 py-5 border-t border-slate-100 bg-slate-50/60">
                        <p class="text-xs font-semibold text-slate-500 mb-3">Conferência com o módulo de Potes</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach(['lucro' => ['Lucro', 'border-t-amber-400', 'Retiradas registradas'], 'reinvestimento' => ['Reinvestimento', 'border-t-sky-400', 'Retiradas registradas'], 'investimento' => ['Investimento', 'border-t-emerald-400', 'Aporte − retiradas']] as $p => [$nome, $borda, $rotReg])
                                @php $c = $conferenciaPotes[$p]; @endphp
                                <div class="rounded-xl bg-white ring-1 ring-slate-200 border-t-4 {{ $borda }} p-4 text-xs space-y-1.5">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-slate-800 text-sm">{{ $nome }}</span>
                                        @if($c['diverge'])
                                            <span class="bg-rose-100 text-rose-700 font-semibold rounded-full px-2 py-0.5">diverge</span>
                                        @else
                                            <span class="bg-emerald-100 text-emerald-700 font-semibold rounded-full px-2 py-0.5">ok</span>
                                        @endif
                                    </div>
                                    <div class="flex justify-between text-slate-500"><span>Planejado (divisão)</span><span>R$ {{ $fmt($c['planejado']) }}</span></div>
                                    <div class="flex justify-between text-slate-600"><span>{{ $rotReg }}</span><span>R$ {{ $fmt($c['registrado']) }}</span></div>
                                    <div class="flex justify-between font-semibold text-slate-800"><span>Identificado no extrato</span><span>R$ {{ $fmt($c['extrato']) }}</span></div>
                                    @if($p === 'reinvestimento' && $prioridadesConcluidas > 0)
                                        <div class="flex justify-between text-sky-700"><span>Prioridades concluídas</span><span>R$ {{ $fmt($prioridadesConcluidas) }}</span></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if(collect($conferenciaPotes)->contains('diverge', true))
                            <p class="text-xs text-rose-600 mt-3">Há diferença entre o que foi registrado nos Potes e o que o extrato identificou. Confira as palavras-chave em Despesas → Configurações: retiradas não reconhecidas continuam contando como "Outras Despesas".</p>
                        @endif
                        @if($transferenciasInternas > 0)
                            <p class="text-xs text-slate-500 mt-3">R$ {{ $fmt($transferenciasInternas) }} em transferências internas / aplicações ficaram fora do DRE (não são receita nem despesa).</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- COMPOSIÇÃO DO DINHEIRO NOVO -->
                    <div class="{{ $card }} p-6 lg:col-span-2">
                        <h3 class="text-base font-bold text-slate-900">De onde veio o dinheiro novo</h3>
                        <p class="text-xs text-slate-500 mt-1">Entradas reais nos extratos deste mês.</p>
                        @php
                            $pctMaq = $receitaCaixa > 0 ? ($maquininhaNoBanco / $receitaCaixa) * 100 : 0;
                            $pctPix = $receitaCaixa > 0 ? 100 - $pctMaq : 0;
                        @endphp
                        <p class="text-3xl font-bold text-emerald-700 mt-4">R$ {{ $fmt($receitaCaixa) }}</p>
                        <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden flex mt-4">
                            <div class="bg-violet-500 h-2.5" style="width: {{ number_format($pctMaq, 2, '.', '') }}%"></div>
                            <div class="bg-emerald-500 h-2.5" style="width: {{ number_format($pctPix, 2, '.', '') }}%"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm mt-4">
                            <div class="flex items-start gap-2">
                                <span class="mt-1.5 w-2.5 h-2.5 rounded-full bg-violet-500 shrink-0"></span>
                                <div>
                                    <p class="text-slate-500 text-xs">Maquininha depositada</p>
                                    <p class="font-semibold text-slate-800">R$ {{ $fmt($maquininhaNoBanco) }} <span class="text-xs font-normal text-slate-400">{{ number_format($pctMaq, 0) }}%</span></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="mt-1.5 w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <div>
                                    <p class="text-slate-500 text-xs">PIX e outras entradas</p>
                                    <p class="font-semibold text-slate-800">R$ {{ $fmt($pixOutros) }} <span class="text-xs font-normal text-slate-400">{{ number_format($pctPix, 0) }}%</span></p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 mt-4">Entrou no Bradesco R$ {{ $fmt($entradasBradesco) }} e no Itaú R$ {{ $fmt($entradasItau) }} (inclui eventuais transferências entre as contas).</p>
                    </div>

                    <!-- MAQUININHA -->
                    <div class="{{ $card }} p-6 lg:col-span-3">
                        <h3 class="text-base font-bold text-slate-900">Maquininha: vendido x recebido</h3>
                        <p class="text-xs text-slate-500 mt-1">Débito, crédito à vista e parcelado. Informativo, não altera o resultado.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                            @foreach([
                                ['Vendas no mês', 'bruto', $vendasBrutas, 'bg-slate-50 text-slate-800'],
                                ['Recebido no mês', 'bruto', $recebimentosBrutos, 'bg-slate-50 text-slate-800'],
                                ['Taxas retidas', 'custo do cartão', $taxasMaquininha, 'bg-rose-50 text-rose-700'],
                                ['Líquido recebido', 'caiu na conta', $recebimentosLiquidos, 'bg-emerald-50 text-emerald-800'],
                                ['A receber', 'líquido estimado', $aReceberLiquido, 'bg-violet-50 text-violet-800'],
                            ] as [$rot, $sub, $v, $tom])
                                <div class="rounded-xl p-3 {{ $tom }}">
                                    <p class="text-[11px] font-semibold opacity-80">{{ $rot }}</p>
                                    <p class="text-base font-bold mt-1 whitespace-nowrap">R$ {{ $fmt($v) }}</p>
                                    <p class="text-[10px] opacity-60">{{ $sub }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5">
                            <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                                <span>Recebido das vendas do ano: <strong class="text-slate-700">R$ {{ $fmt($recebidoAcum) }}</strong> de R$ {{ $fmt($vendasAcum) }}</span>
                                <span class="font-semibold text-slate-700">{{ $pct($pctRecebido) }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-violet-100 overflow-hidden">
                                <div class="bg-violet-500 h-2 rounded-full" style="width: {{ number_format($pctRecebido, 2, '.', '') }}%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-2">A receber = vendas acumuladas no ano − o que já foi depositado, descontada a taxa média. Parcelas de vendas de anos anteriores não entram.</p>
                        </div>
                    </div>
                </div>

                <!-- CONTAS BANCÁRIAS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach([
                        ['Conta Bradesco', 'border-t-blue-500', 'text-blue-900', $saldoInicialBradesco, $entradasBradesco, $saidasBradesco, $saldoFinalBradesco],
                        ['Conta Itaú', 'border-t-orange-500', 'text-orange-900', $saldoInicialItau, $entradasItau, $saidasItau, $saldoFinalItau],
                        ['Total das contas', 'border-t-slate-700', 'text-slate-900', $saldoInicialTotal, $entradasTotalContas, $saidasTotalContas, $saldoFinalTotal],
                    ] as [$nome, $borda, $cor, $ini, $ent, $sai, $fim])
                        <div class="{{ $card }} p-5 border-t-4 {{ $borda }}">
                            <h4 class="font-bold {{ $cor }} mb-3">{{ $nome }}</h4>
                            <dl class="space-y-1.5 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Saldo inicial</dt><dd class="font-medium text-slate-700">R$ {{ $fmt($ini) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-emerald-700">Entradas (+)</dt><dd class="font-medium text-emerald-700">R$ {{ $fmt($ent) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-rose-600">Saídas (−)</dt><dd class="font-medium text-rose-600">R$ {{ $fmt($sai) }}</dd></div>
                                <div class="flex justify-between pt-2 mt-1 border-t border-slate-100"><dt class="font-semibold {{ $cor }}">Saldo final</dt><dd class="font-bold {{ $cor }}">R$ {{ $fmt($fim) }}</dd></div>
                            </dl>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
