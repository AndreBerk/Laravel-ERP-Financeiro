{{-- Glossário do DRE. Uso: @include('dre.partials.glossario', ['modo' => 'web'|'print']) --}}
@php
    $modo = $modo ?? 'web';
    $itens = [
        [
            'titulo' => 'Dinheiro novo',
            'formula' => 'PIX + depósitos da maquininha que caíram no período',
            'texto' => 'É o dinheiro que realmente "pingou" nas contas Bradesco e Itaú (regime de caixa). Vale o dia em que o dinheiro entrou, não o dia da venda: um depósito de hoje pode ser de uma venda parcelada de meses atrás. Não entram o saldo que já estava na conta nem transferências entre suas próprias contas.',
            'cor' => 'emerald',
        ],
        [
            'titulo' => 'Resultado operacional',
            'formula' => 'Dinheiro novo − despesas da loja',
            'texto' => 'O que a loja gerou depois de pagar os custos do dia a dia (aluguel, fornecedores, tarifas, impostos). É o termômetro da saúde do negócio antes de qualquer divisão de lucro. Positivo: a operação se paga e sobra. Negativo: saiu mais do que entrou.',
            'cor' => 'sky',
        ],
        [
            'titulo' => 'Destinação aos potes',
            'formula' => 'Lucro dos sócios + reinvestimento + reserva',
            'texto' => 'Não é despesa da loja. Mostra para onde foi guardado o dinheiro que sobrou: retirada de lucro dos sócios, reinvestimento no negócio (estoque, melhorias) e reserva/aplicações (resgates abatem). Por isso aparece depois do resultado operacional.',
            'cor' => 'amber',
        ],
        [
            'titulo' => 'Maquininha a receber',
            'formula' => 'Vendas no cartão − o que já foi depositado, menos a taxa média',
            'texto' => 'Capital de giro "preso": vendas já feitas que ainda vão cair na conta (parcelas futuras do crédito, débito em D+1). É seu, mas ainda não está disponível. As taxas retidas são o custo de vender no cartão.',
            'cor' => 'violet',
        ],
        [
            'titulo' => 'Resultado líquido de caixa',
            'formula' => 'Resultado operacional − destinação aos potes',
            'texto' => 'O que ficou livre em caixa depois de pagar as despesas e separar os potes. Negativo significa que os potes usaram dinheiro que já estava na conta.',
            'cor' => 'slate',
        ],
    ];
    $tons = [
        'emerald' => ['borda' => 'border-emerald-400', 'titulo' => 'text-emerald-800', 'fundo' => 'bg-emerald-50'],
        'sky' => ['borda' => 'border-sky-400', 'titulo' => 'text-sky-800', 'fundo' => 'bg-sky-50'],
        'amber' => ['borda' => 'border-amber-400', 'titulo' => 'text-amber-800', 'fundo' => 'bg-amber-50'],
        'violet' => ['borda' => 'border-violet-400', 'titulo' => 'text-violet-800', 'fundo' => 'bg-violet-50'],
        'slate' => ['borda' => 'border-slate-400', 'titulo' => 'text-slate-800', 'fundo' => 'bg-slate-50'],
    ];
@endphp

@if($modo === 'print')
    <section class="glossario mt-5 pt-3 border-t-2 border-slate-800">
        <h2 class="text-[11px] font-black uppercase tracking-wide text-slate-900">Glossário financeiro: como ler este relatório</h2>
        <p class="text-[9px] text-slate-500 mt-0.5 mb-2">Este relatório usa o <strong>regime de caixa</strong>: conta o dinheiro na data em que entrou ou saiu do banco, não na data da venda ou da compra.</p>
        <div class="grid grid-cols-2 gap-2">
            @foreach($itens as $item)
                <div class="evitar-quebra border-l-4 {{ $tons[$item['cor']]['borda'] }} {{ $tons[$item['cor']]['fundo'] }} rounded-r px-2.5 py-1.5">
                    <p class="text-[10px] font-black {{ $tons[$item['cor']]['titulo'] }}">{{ $item['titulo'] }}</p>
                    <p class="text-[8.5px] font-semibold text-slate-600 italic">{{ $item['formula'] }}</p>
                    <p class="text-[8.5px] leading-snug text-slate-700 mt-0.5">{{ $item['texto'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@else
    <details class="group bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm" open>
        <summary class="flex items-center justify-between gap-3 px-6 py-4 cursor-pointer select-none list-none">
            <div class="flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-600 text-sm font-bold">?</span>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Entenda o cálculo</h3>
                    <p class="text-xs text-slate-500">Regime de caixa: vale a data em que o dinheiro entrou ou saiu do banco.</p>
                </div>
            </div>
            <span class="text-xs font-semibold text-slate-500 group-open:hidden">Mostrar</span>
            <span class="text-xs font-semibold text-slate-500 hidden group-open:inline">Ocultar</span>
        </summary>
        <div class="px-6 pb-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
            @foreach($itens as $item)
                <div class="rounded-xl border-t-4 {{ $tons[$item['cor']]['borda'] }} {{ $tons[$item['cor']]['fundo'] }} p-4">
                    <p class="text-sm font-bold {{ $tons[$item['cor']]['titulo'] }}">{{ $item['titulo'] }}</p>
                    <p class="text-[11px] font-semibold text-slate-500 mt-1">{{ $item['formula'] }}</p>
                    <p class="text-xs leading-relaxed text-slate-700 mt-2">{{ $item['texto'] }}</p>
                </div>
            @endforeach
        </div>
    </details>
@endif
