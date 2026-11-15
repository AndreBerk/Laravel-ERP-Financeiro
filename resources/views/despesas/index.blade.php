@php
    $br = fn($v) => 'R$ ' . number_format($v ?? 0, 2, ',', '.');
    $meses = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
    $mesesLongos = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];

    // Design system (mesmo padrão do DRE)
    $card  = 'bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm';
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500';
    $label = 'block text-xs font-medium text-slate-600 mb-1';
    $btnPrimario   = 'inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition';
    $btnSecundario = 'inline-flex items-center justify-center gap-2 bg-white ring-1 ring-slate-300 hover:bg-slate-100 text-slate-700 text-sm font-semibold px-4 py-2 rounded-lg transition';
    $th = 'px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500';

    // Visual de cada cofre (classes completas para o Tailwind enxergar)
    $visual = [
        'reinvestimento' => ['titulo' => 'Reinvestimento', 'desc' => 'Acervo, compras e marketing', 'borda' => 'border-t-sky-500', 'texto' => 'text-sky-700', 'fundo' => 'bg-sky-50', 'btn' => 'bg-sky-600 hover:bg-sky-700', 'rotulo' => 'Registrar uso do fundo', 'campo' => 'No que foi gasto?'],
        'lucro'          => ['titulo' => 'Lucro dos sócios', 'desc' => 'Dividendos', 'borda' => 'border-t-amber-500', 'texto' => 'text-amber-700', 'fundo' => 'bg-amber-50', 'btn' => 'bg-amber-500 hover:bg-amber-600', 'rotulo' => 'Distribuir lucro', 'campo' => 'Quem recebeu?'],
        'investimento'   => ['titulo' => 'Investimento (CDI)', 'desc' => 'Reserva + pote de investimento', 'borda' => 'border-t-emerald-500', 'texto' => 'text-emerald-700', 'fundo' => 'bg-emerald-50', 'btn' => 'bg-emerald-600 hover:bg-emerald-700', 'rotulo' => 'Registrar resgate', 'campo' => 'Motivo do resgate'],
    ];
    $totalGuardado = collect($cofres)->sum('saldo');
    $tiposDespesa = [
        'unica' => ['Única', 'bg-slate-100 text-slate-700'],
        'fixa' => ['Fixa', 'bg-sky-100 text-sky-800'],
        'parcelada' => ['Parcelada', 'bg-violet-100 text-violet-800'],
    ];

    // ---- Prioridades de investimento da empresa ----
    $prioridades = $prioridades ?? collect();
    $prioVisual = [
        'urgente'      => ['rotulo' => 'Urgente',      'ponto' => 'bg-rose-500',    'borda' => 'border-l-rose-500',    'badge' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'importante'   => ['rotulo' => 'Importante',   'ponto' => 'bg-amber-500',   'borda' => 'border-l-amber-400',   'badge' => 'bg-amber-50 text-amber-800 ring-amber-200'],
        'pode_esperar' => ['rotulo' => 'Pode esperar', 'ponto' => 'bg-emerald-500', 'borda' => 'border-l-emerald-400', 'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
    ];
    $statusVisual = ['pendente' => 'Pendente', 'em_andamento' => 'Em andamento', 'concluido' => 'Concluído'];
    $categoriasPrioridade = ['Infraestrutura / Reforma', 'Acervo (trajes)', 'Equipamentos', 'Marketing', 'Tecnologia', 'Outros'];
    $ordemPrio = ['urgente' => 0, 'importante' => 1, 'pode_esperar' => 2];

    $prioridadesOrdenadas = $prioridades->sortBy(fn($p) =>
        ($p->status === 'concluido' ? '1' : '0') . ($ordemPrio[$p->prioridade] ?? 1) . ($p->prazo ? $p->prazo->format('Ymd') : '99999999')
    )->values();

    $abertas        = $prioridades->where('status', '!=', 'concluido');
    $totalPrevisto  = $abertas->sum('valor_previsto');
    $urgentes       = $abertas->where('prioridade', 'urgente');
    $totalUrgente   = $urgentes->sum('valor_previsto');
    $qtdUrgente     = $urgentes->count();
    $emAndamento    = $abertas->where('status', 'em_andamento');
    $qtdAndamento   = $emAndamento->count();
    $totalAndamento = $emAndamento->sum('valor_previsto');
    $totalGasto     = $prioridades->where('status', 'concluido')->sum(fn($p) => $p->valor_gasto ?? $p->valor_previsto);
    $saldoReinv     = $cofres['reinvestimento']['saldo'] ?? 0;
    $cobertura      = $saldoReinv - $totalPrevisto;
    $pctCobertura   = $totalPrevisto > 0 ? max(0, min(100, (int) round($saldoReinv / $totalPrevisto * 100))) : 100;

    $formAberto = old('_form'); // reabre o modal certo quando a validação falha
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Planejamento Financeiro</h2>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen"
         x-data="{ aba: (['painel','despesas','cofres','config','prioridades'].includes(localStorage.getItem('despAba')) ? localStorage.getItem('despAba') : 'painel') }"
         x-init="$watch('aba', v => localStorage.setItem('despAba', v))">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 tabular-nums">

            {{-- CABEÇALHO --}}
            <div class="{{ $card }} p-6 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Despesas, potes e cofres</p>
                    <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $mesesLongos[$mes] }} de {{ $ano }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Distribua o faturamento nos potes, controle as despesas do mês e acompanhe os cofres.</p>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <select name="mes" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm py-2 focus:border-slate-500 focus:ring-slate-500">
                        @foreach($mesesLongos as $n => $m)<option value="{{ $n }}" @selected($n==$mes)>{{ $m }}</option>@endforeach
                    </select>
                    <input type="number" name="ano" value="{{ $ano }}" onchange="this.form.submit()" class="w-24 rounded-lg border-slate-300 text-sm py-2 focus:border-slate-500 focus:ring-slate-500">
                </form>
            </div>

            {{-- MENSAGENS --}}
            @if(session('success'))
                <div class="flex items-start gap-3 rounded-xl bg-emerald-50 ring-1 ring-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="rounded-xl bg-rose-50 ring-1 ring-rose-200 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- ABAS --}}
            <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
                <nav class="inline-flex gap-1 rounded-xl bg-white p-1 ring-1 ring-slate-200 shadow-sm whitespace-nowrap">
                    @foreach(['painel' => 'Painel', 'despesas' => 'Despesas do mês', 'cofres' => 'Cofres', 'prioridades' => 'Prioridades de investimento', 'config' => 'Configurações'] as $k => $r)
                        <button type="button" @click="aba='{{ $k }}'"
                                :class="aba==='{{ $k }}' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                                class="px-4 py-2 rounded-lg text-sm font-semibold transition">{{ $r }}</button>
                    @endforeach
                </nav>
            </div>

            {{-- ======================= PAINEL ======================= --}}
            <div x-show="aba==='painel'" class="space-y-6">

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="{{ $card }} p-5 border-t-4 border-t-emerald-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Faturamento do mês</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">{{ $br($faturamentoMes->faturamento_informado ?? 0) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Valor informado para dividir nos potes.</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-sky-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Verba da operação</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">{{ $br($faturamentoMes->valor_operacao ?? 0) }}</p>
                        <p class="text-xs mt-2 font-semibold {{ $saldoOperacao >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $saldoOperacao >= 0 ? 'Sobra' : 'Estourou' }}: {{ $br(abs($saldoOperacao)) }}
                        </p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-rose-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Despesas do mês</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">{{ $br($totalDespesas) }}</p>
                        <p class="text-xs text-slate-500 mt-2">{{ $despesas->count() }} {{ $despesas->count() === 1 ? 'lançamento' : 'lançamentos' }} &middot; fixas {{ $br($totalFixas) }}</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-amber-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Guardado nos cofres</p>
                        <p class="text-2xl font-bold text-slate-900 mt-2">{{ $br($totalGuardado) }}</p>
                        <p class="text-xs text-slate-500 mt-2">Reinvestimento + lucro + investimento.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Entrada do mês --}}
                    <div class="{{ $card }} p-6">
                        <h3 class="text-base font-bold text-slate-900">Entrada do mês</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-4">Informe o faturamento líquido. A reserva de investimento sai primeiro; o resto é dividido nos potes.</p>
                        <form action="{{ route('potes.faturamento') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="mes" value="{{ $mes }}">
                            <input type="hidden" name="ano" value="{{ $ano }}">
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">R$</span>
                                <input type="number" step="0.01" min="0" name="faturamento_informado" required
                                       value="{{ $faturamentoMes->faturamento_informado ?? '' }}" placeholder="0,00"
                                       class="{{ $input }} pl-10 py-2.5 text-lg font-bold text-slate-900">
                            </div>
                            <button class="{{ $btnPrimario }} w-full !bg-emerald-600 hover:!bg-emerald-700">Distribuir nos potes</button>
                        </form>
                        @if($faturamentoMes)
                            <dl class="mt-5 pt-4 border-t border-slate-100 text-sm space-y-2">
                                @foreach([
                                    ['Reserva de investimento', null, $faturamentoMes->valor_reserva ?? 0, 'bg-emerald-300'],
                                    ['Operação', $config->pct_operacao, $faturamentoMes->valor_operacao, 'bg-sky-500'],
                                    ['Reinvestimento', $config->pct_reinvestimento, $faturamentoMes->valor_reinvestimento, 'bg-sky-300'],
                                    ['Lucro', $config->pct_lucro, $faturamentoMes->valor_lucro, 'bg-amber-500'],
                                    ['Investimento (+ reserva)', $config->pct_investimento, $faturamentoMes->valor_investimento ?? 0, 'bg-emerald-500'],
                                ] as [$rot, $pctPote, $v, $cor])
                                    <div class="flex justify-between items-center">
                                        <dt class="flex items-center gap-2 text-slate-600">
                                            <span class="w-2 h-2 rounded-full {{ $cor }}"></span>{{ $rot }}
                                            @if(!is_null($pctPote))<span class="text-xs text-slate-400">{{ rtrim(rtrim(number_format($pctPote, 2, ',', ''), '0'), ',') }}%</span>@endif
                                        </dt>
                                        <dd class="font-semibold text-slate-800">{{ $br($v) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </div>

                    {{-- Termômetro --}}
                    <div class="{{ $card }} p-6 lg:col-span-2 flex flex-col">
                        <h3 class="text-base font-bold text-slate-900">Termômetro de despesas fixas</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-5">Compara as despesas fixas deste mês com as do mês anterior para flagrar aumento silencioso de custo.</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-medium text-slate-500">Mês anterior</p>
                                <p class="text-xl font-bold text-slate-800 mt-1">{{ $br($fixasAnt) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-medium text-slate-500">Este mês</p>
                                <p class="text-xl font-bold text-slate-800 mt-1">{{ $br($totalFixas) }}</p>
                            </div>
                            @if(is_null($variacaoFixas))
                                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium text-slate-500">Variação</p><p class="text-xl font-bold text-slate-400 mt-1">sem base</p></div>
                            @elseif($variacaoFixas > 0)
                                <div class="rounded-xl bg-rose-50 p-4"><p class="text-xs font-medium text-rose-700">Variação</p><p class="text-xl font-bold text-rose-700 mt-1">▲ {{ $variacaoFixas }}%</p></div>
                            @elseif($variacaoFixas < 0)
                                <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs font-medium text-emerald-700">Variação</p><p class="text-xl font-bold text-emerald-700 mt-1">▼ {{ abs($variacaoFixas) }}%</p></div>
                            @else
                                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium text-slate-500">Variação</p><p class="text-xl font-bold text-slate-600 mt-1">estável</p></div>
                            @endif
                        </div>
                        @if($rendimentoEstimado > 0)
                            <p class="mt-auto pt-5 text-sm text-emerald-800">
                                <span class="block rounded-xl bg-emerald-50 ring-1 ring-emerald-100 px-4 py-3">
                                    Rendimento estimado do cofre de investimento: <strong>{{ $br($rendimentoEstimado) }}/mês</strong>
                                    <span class="text-emerald-700/80">({{ $config->pct_do_cdi }}% do CDI a {{ $config->taxa_cdi }}% a.a.)</span>
                                </span>
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Histórico --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="{{ $card }} overflow-hidden">
                        <h4 class="px-5 py-4 text-sm font-bold text-slate-900 border-b border-slate-100">Histórico de faturamentos</h4>
                        <div class="overflow-auto max-h-72">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 sticky top-0">
                                    <tr><th class="{{ $th }}">Mês</th><th class="{{ $th }} !text-right">Faturamento</th><th class="{{ $th }} !text-right">Operação</th><th class="{{ $th }} !text-right">Reinv.</th><th class="{{ $th }} !text-right">Lucro</th><th class="{{ $th }} !text-right">Invest.</th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @forelse($historicoFaturamentos as $h)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-2.5 text-slate-600">{{ str_pad($h->mes,2,'0',STR_PAD_LEFT) }}/{{ $h->ano }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-slate-900">{{ $br($h->faturamento_informado) }}</td>
                                        <td class="px-4 py-2.5 text-right text-slate-600">{{ $br($h->valor_operacao) }}</td>
                                        <td class="px-4 py-2.5 text-right text-slate-600">{{ $br($h->valor_reinvestimento) }}</td>
                                        <td class="px-4 py-2.5 text-right text-slate-600">{{ $br($h->valor_lucro) }}</td>
                                        <td class="px-4 py-2.5 text-right text-slate-600">{{ $br($h->valor_investimento ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Nenhum faturamento registrado.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="{{ $card }} overflow-hidden">
                        <h4 class="px-5 py-4 text-sm font-bold text-slate-900 border-b border-slate-100">Retiradas dos cofres</h4>
                        <div class="overflow-auto max-h-72">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 sticky top-0">
                                    <tr><th class="{{ $th }}">Data</th><th class="{{ $th }}">Cofre</th><th class="{{ $th }}">Quem / finalidade</th><th class="{{ $th }} !text-right">Valor</th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @forelse($historicoSaques as $s)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-2.5 text-slate-600">{{ \Carbon\Carbon::parse($s->data_saque)->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $visual[$s->tipo_pote]['fundo'] ?? 'bg-slate-100' }} {{ $visual[$s->tipo_pote]['texto'] ?? 'text-slate-700' }}">{{ $visual[$s->tipo_pote]['titulo'] ?? $s->tipo_pote }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-slate-700">{{ $s->descricao_beneficiario }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-rose-600">−{{ $br($s->valor) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">Nenhuma retirada registrada.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================= DESPESAS ======================= --}}
            <div x-show="aba==='despesas'" x-cloak class="space-y-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="{{ $card }} p-5">
                        <p class="text-xs font-medium text-slate-500">Total do mês</p>
                        <p class="text-xl font-bold text-rose-600 mt-1">{{ $br($totalDespesas) }}</p>
                    </div>
                    <div class="{{ $card }} p-5">
                        <p class="text-xs font-medium text-slate-500">Fixas</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ $br($totalFixas) }}</p>
                    </div>
                    <div class="{{ $card }} p-5">
                        <p class="text-xs font-medium text-slate-500">Verba da operação</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ $br($faturamentoMes->valor_operacao ?? 0) }}</p>
                    </div>
                    <div class="{{ $card }} p-5">
                        <p class="text-xs font-medium text-slate-500">{{ $saldoOperacao >= 0 ? 'Ainda sobra' : 'Passou da verba' }}</p>
                        <p class="text-xl font-bold mt-1 {{ $saldoOperacao >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $br(abs($saldoOperacao)) }}</p>
                    </div>
                </div>

                <div class="{{ $card }} overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Despesas de {{ $mesesLongos[$mes] }}/{{ $ano }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Fixas se repetem todo mês. Parceladas criam uma linha em cada mês futuro automaticamente.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form action="{{ route('potes.fixas.copiar') }}" method="POST">
                                @csrf
                                <input type="hidden" name="mes" value="{{ $mes }}"><input type="hidden" name="ano" value="{{ $ano }}">
                                <button class="{{ $btnSecundario }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.1 15A7 7 0 0018.4 17M18.9 9A7 7 0 005.6 7"/></svg>
                                    Copiar fixas do mês anterior
                                </button>
                            </form>
                            <button type="button" x-on:click="$dispatch('open-modal', 'nova-despesa')" class="{{ $btnPrimario }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                                Nova despesa
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr><th class="{{ $th }} w-20">Data</th><th class="{{ $th }}">Descrição</th><th class="{{ $th }} w-32">Tipo</th><th class="{{ $th }} !text-right w-36">Valor</th><th class="w-14"></th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            @forelse($despesas as $d)
                                @php [$tipoRot, $tipoCor] = $tiposDespesa[$d->recorrencia] ?? [$d->recorrencia, 'bg-slate-100 text-slate-700']; @endphp
                                <tr class="group hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-500">{{ \Carbon\Carbon::parse($d->data_despesa)->format('d/m') }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $d->descricao }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $tipoCor }}">{{ $tipoRot }}</span></td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $br($d->valor) }}</td>
                                    <td class="px-2 py-3 text-center">
                                        <form action="{{ route('potes.despesa.destroy', $d->id) }}" method="POST" onsubmit="return confirm('Excluir esta despesa?')">
                                            @csrf @method('DELETE')
                                            <button class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Excluir" aria-label="Excluir {{ $d->descricao }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V4h6v3m-7 0l1 13h6l1-13"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-14 text-center">
                                        <p class="text-sm font-medium text-slate-500">Nenhuma despesa neste mês.</p>
                                        <button type="button" x-on:click="$dispatch('open-modal', 'nova-despesa')" class="mt-2 text-sm font-semibold text-slate-900 underline underline-offset-4">Lançar a primeira</button>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                            @if($despesas->isNotEmpty())
                                <tfoot class="border-t-2 border-slate-200 bg-slate-50/60">
                                    <tr><td colspan="3" class="px-4 py-2.5 text-right text-xs font-medium text-slate-500">Fixas</td><td class="px-4 py-2.5 text-right font-semibold text-slate-700">{{ $br($totalFixas) }}</td><td></td></tr>
                                    <tr><td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-slate-700">Total consumido da operação</td><td class="px-4 py-3 text-right text-base font-bold text-rose-600">{{ $br($totalDespesas) }}</td><td></td></tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                {{-- Modal: nova despesa --}}
                <x-modal name="nova-despesa" maxWidth="lg" :show="$formAberto === 'nova-despesa'" focusable>
                    <form action="{{ route('potes.despesa') }}" method="POST" x-data="{ rec: @js(old('recorrencia', 'unica')) }" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="_form" value="nova-despesa">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Nova despesa</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Sai da verba da operação do mês.</p>
                            </div>
                            <button type="button" x-on:click="$dispatch('close')" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100" aria-label="Fechar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                            </button>
                        </div>
                        <div>
                            <label class="{{ $label }}">Descrição</label>
                            <input type="text" name="descricao" value="{{ old('descricao') }}" required class="{{ $input }}" placeholder="Ex.: Aluguel, fornecedor...">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $label }}">Valor (R$)</label>
                                <input type="number" step="0.01" min="0.01" name="valor" value="{{ old('valor') }}" required class="{{ $input }} font-semibold" placeholder="0,00">
                            </div>
                            <div>
                                <label class="{{ $label }}">Data</label>
                                <input type="date" name="data_despesa" value="{{ old('data_despesa', now()->setYear($ano)->setMonth($mes)->format('Y-m-d')) }}" required class="{{ $input }}">
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Tipo</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach(['unica' => 'Única', 'fixa' => 'Fixa (todo mês)', 'parcelada' => 'Parcelada'] as $valor => $rot)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="recorrencia" value="{{ $valor }}" x-model="rec" class="peer sr-only">
                                        <span class="block text-center rounded-lg px-2 py-2 text-xs font-semibold ring-1 ring-slate-300 text-slate-600 peer-checked:bg-slate-900 peer-checked:text-white peer-checked:ring-slate-900 transition">{{ $rot }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div x-show="rec==='parcelada'" x-cloak>
                            <label class="{{ $label }}">Número de parcelas</label>
                            <input type="number" name="total_parcelas" min="2" max="60" value="{{ old('total_parcelas') }}" :required="rec==='parcelada'" class="{{ $input }}" placeholder="Ex.: 10">
                            <p class="text-[11px] text-slate-400 mt-1">O valor acima é o de cada parcela.</p>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" x-on:click="$dispatch('close')" class="{{ $btnSecundario }}">Cancelar</button>
                            <button class="{{ $btnPrimario }}">Adicionar despesa</button>
                        </div>
                    </form>
                </x-modal>
            </div>

            {{-- ======================= COFRES ======================= --}}
            <div x-show="aba==='cofres'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                @foreach($cofres as $chave => $c)
                    @php $v = $visual[$chave]; @endphp
                    <div class="{{ $card }} border-t-4 {{ $v['borda'] }} p-6 flex flex-col gap-4">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <h3 class="text-base font-bold {{ $v['texto'] }}">{{ $v['titulo'] }} <span class="text-sm font-medium text-slate-400">{{ rtrim(rtrim(number_format($c['pct'], 2, ',', ''), '0'), ',') }}%</span></h3>
                                <p class="text-xs text-slate-500">{{ $v['desc'] }}</p>
                            </div>
                            @if($c['ciclo'])
                                <span class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $c['aberto'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $c['aberto'] ? 'Resgate aberto' : 'Aguardando ciclo' }}
                                </span>
                            @endif
                        </div>

                        @if($c['ciclo'])
                            <div class="flex gap-1">
                                @foreach($meses as $n => $m)
                                    <span class="flex-1 text-center text-[9px] font-semibold rounded py-1 {{ in_array($n, $c['ciclo']) ? ($n==$mes ? 'bg-emerald-500 text-white' : 'bg-slate-800 text-white') : 'bg-slate-100 text-slate-400' }}">{{ $m }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="rounded-xl {{ $v['fundo'] }} py-6 text-center">
                            <p class="text-xs font-medium text-slate-500">Saldo acumulado</p>
                            <p class="text-3xl font-bold {{ $v['texto'] }} mt-1">{{ $br($c['saldo']) }}</p>
                            @if($chave === 'investimento' && $rendimentoEstimado > 0)
                                <p class="text-xs text-emerald-700 font-medium mt-1">≈ {{ $br($rendimentoEstimado) }}/mês de rendimento</p>
                            @endif
                        </div>

                        @if($chave === 'lucro')
                            <div class="rounded-xl ring-1 ring-slate-200 p-4 text-sm space-y-1.5">
                                <p class="text-xs font-semibold text-amber-700 mb-1">Projeção com o saldo atual</p>
                                @foreach($socios as $s)
                                    <p class="flex justify-between text-slate-600"><span>{{ $s['nome'] }} <span class="text-slate-400">{{ $s['cota'] }}%</span></span><span class="font-semibold text-slate-800">{{ $br($c['saldo'] * $s['cota'] / 100) }}</span></p>
                                @endforeach
                            </div>
                        @endif

                        <button type="button" x-on:click="$dispatch('open-modal', 'saque-{{ $chave }}')" class="mt-auto w-full {{ $v['btn'] }} text-white text-sm font-semibold py-2.5 rounded-lg transition">{{ $v['rotulo'] }}</button>
                    </div>

                    {{-- Modal: retirada do cofre --}}
                    <x-modal name="saque-{{ $chave }}" maxWidth="md" :show="$formAberto === 'saque-' . $chave" focusable>
                        <form action="{{ route('potes.saque') }}" method="POST" class="p-6 space-y-4" x-data="{ dividir: true }">
                            @csrf
                            <input type="hidden" name="_form" value="saque-{{ $chave }}">
                            <input type="hidden" name="tipo_pote" value="{{ $chave }}">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">{{ $v['rotulo'] }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Cofre {{ $v['titulo'] }} &middot; disponível <strong class="{{ $v['texto'] }}">{{ $br($c['saldo']) }}</strong></p>
                            </div>
                            <div>
                                <label class="{{ $label }}">Valor (R$)</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $c['saldo'] }}" name="valor" value="{{ $chave==='lucro' ? $c['saldo'] : '' }}" required class="{{ $input }} font-semibold">
                            </div>
                            @if($chave === 'lucro')
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="distribuir" value="1" x-model="dividir" checked class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                    Dividir automaticamente pelas cotas dos sócios
                                </label>
                            @endif
                            <div @if($chave === 'lucro') x-show="!dividir" x-cloak @endif>
                                <label class="{{ $label }}">{{ $chave==='lucro' ? 'Beneficiário' : $v['campo'] }}</label>
                                <input type="text" name="descricao_beneficiario" class="{{ $input }}">
                            </div>
                            <div>
                                <label class="{{ $label }}">Data</label>
                                <input type="date" name="data_saque" value="{{ now()->format('Y-m-d') }}" required class="{{ $input }}">
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" x-on:click="$dispatch('close')" class="{{ $btnSecundario }}">Cancelar</button>
                                <button class="{{ $btnPrimario }}">Confirmar</button>
                            </div>
                        </form>
                    </x-modal>
                @endforeach
            </div>

            {{-- ======================= PRIORIDADES DE INVESTIMENTO ======================= --}}
            <div x-show="aba==='prioridades'" x-cloak class="space-y-6" x-data="{ filtro: 'abertas' }">

                <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="{{ $card }} p-5">
                        <p class="text-xs font-medium text-slate-500">Previsto em aberto</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ $br($totalPrevisto) }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $abertas->count() }} {{ $abertas->count() === 1 ? 'item' : 'itens' }}</p>
                    </div>
                    <div class="{{ $card }} p-5 border-t-4 border-t-rose-500">
                        <p class="text-xs font-medium text-rose-700">Urgente</p>
                        <p class="text-xl font-bold text-rose-600 mt-1">{{ $br($totalUrgente) }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $qtdUrgente }} {{ $qtdUrgente === 1 ? 'item' : 'itens' }}</p>
                    </div>
                    <div class="{{ $card }} p-5">
                        <p class="text-xs font-medium text-slate-500">Em andamento</p>
                        <p class="text-xl font-bold text-sky-700 mt-1">{{ $qtdAndamento }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $br($totalAndamento) }} previstos</p>
                    </div>
                    <div class="{{ $card }} p-5">
                        <p class="text-xs font-medium text-slate-500">Já investido</p>
                        <p class="text-xl font-bold text-emerald-700 mt-1">{{ $br($totalGasto) }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $prioridades->where('status','concluido')->count() }} concluídos</p>
                    </div>
                </div>

                {{-- Cobertura pelo cofre de Reinvestimento --}}
                <div class="{{ $card }} p-6">
                    <div class="flex flex-wrap justify-between items-end gap-3 mb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Cobertura pelo cofre de Reinvestimento</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Saldo atual do cofre comparado ao total previsto das prioridades em aberto.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-slate-500">Saldo do cofre</p>
                            <p class="text-xl font-bold text-sky-700">{{ $br($saldoReinv) }}</p>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full {{ $cobertura >= 0 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $pctCobertura }}%"></div>
                    </div>
                    <p class="mt-2 text-sm font-medium {{ $cobertura >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                        @if($totalPrevisto <= 0)
                            Nenhuma prioridade em aberto.
                        @elseif($cobertura >= 0)
                            O cofre cobre {{ $pctCobertura }}%: sobram {{ $br($cobertura) }} depois de realizar tudo.
                        @else
                            O cofre cobre {{ $pctCobertura }}%: faltam {{ $br(abs($cobertura)) }} para realizar tudo.
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 items-center justify-between">
                    <div class="inline-flex flex-wrap gap-1 rounded-xl bg-white p-1 ring-1 ring-slate-200 shadow-sm">
                        @foreach(['abertas' => 'Em aberto', 'pendente' => 'Pendentes', 'em_andamento' => 'Em andamento', 'concluido' => 'Concluídas', 'todas' => 'Todas'] as $k => $r)
                            <button type="button" @click="filtro='{{ $k }}'" :class="filtro==='{{ $k }}' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">{{ $r }}</button>
                        @endforeach
                    </div>
                    <button type="button" x-on:click="$dispatch('open-modal', 'nova-prioridade')" class="{{ $btnPrimario }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        Nova prioridade
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse($prioridadesOrdenadas as $p)
                        @php
                            $pv = $prioVisual[$p->prioridade] ?? $prioVisual['importante'];
                            $concluido = $p->status === 'concluido';
                            $atrasado = $p->prazo && !$concluido && $p->prazo->isPast() && !$p->prazo->isToday();
                        @endphp
                        <div x-show="filtro==='todas' || (filtro==='abertas' && '{{ $p->status }}' !== 'concluido') || filtro==='{{ $p->status }}'"
                             class="{{ $card }} border-l-4 {{ $pv['borda'] }} p-5 {{ $concluido ? 'opacity-60' : '' }}">
                            <div class="flex flex-wrap justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2 py-0.5 rounded-full ring-1 {{ $pv['badge'] }}"><span class="w-1.5 h-1.5 rounded-full {{ $pv['ponto'] }}"></span>{{ $pv['rotulo'] }}</span>
                                        @if($p->categoria)<span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $p->categoria }}</span>@endif
                                        @if($p->status === 'em_andamento')<span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-sky-100 text-sky-800">Em andamento</span>@endif
                                        @if($concluido)<span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Concluído</span>@endif
                                        @if($atrasado)<span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-rose-600 text-white">Prazo vencido</span>@endif
                                    </div>
                                    <p class="font-semibold text-slate-900 {{ $concluido ? 'line-through' : '' }}">{{ $p->titulo }}</p>
                                    @if($p->observacao)<p class="text-sm text-slate-500 mt-0.5">{{ $p->observacao }}</p>@endif
                                    @if($p->prazo)<p class="text-xs mt-1.5 {{ $atrasado ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">Prazo: {{ $p->prazo->format('d/m/Y') }}</p>@endif
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-medium text-slate-400">{{ $concluido ? 'Valor gasto' : 'Gasto previsto' }}</p>
                                    <p class="text-xl font-bold {{ $concluido ? 'text-emerald-700' : 'text-slate-900' }}">{{ $br($concluido ? ($p->valor_gasto ?? $p->valor_previsto) : $p->valor_previsto) }}</p>
                                    @if($concluido && $p->valor_gasto !== null && (float)$p->valor_gasto !== (float)$p->valor_previsto)
                                        <p class="text-[11px] font-medium {{ $p->valor_gasto > $p->valor_previsto ? 'text-rose-600' : 'text-emerald-600' }}">previsto {{ $br($p->valor_previsto) }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 mt-4 pt-3 border-t border-slate-100">
                                <form action="{{ route('potes.prioridade.update', $p->id) }}" method="POST" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <label class="text-xs font-medium text-slate-500">Status</label>
                                    <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1 focus:border-slate-500 focus:ring-slate-500">
                                        @foreach($statusVisual as $sk => $sr)<option value="{{ $sk }}" @selected($p->status===$sk)>{{ $sr }}</option>@endforeach
                                    </select>
                                </form>
                                <button type="button" x-on:click="$dispatch('open-modal', 'editar-prioridade-{{ $p->id }}')" class="text-xs font-semibold text-slate-700 hover:text-slate-900 underline-offset-4 hover:underline">Editar</button>
                                <form action="{{ route('potes.prioridade.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Excluir esta prioridade?')" class="ml-auto">
                                    @csrf @method('DELETE')<button class="text-xs font-semibold text-rose-600 hover:text-rose-800">Excluir</button>
                                </form>
                            </div>
                        </div>

                        {{-- Modal: editar prioridade --}}
                        <x-modal name="editar-prioridade-{{ $p->id }}" maxWidth="xl" focusable>
                            <form action="{{ route('potes.prioridade.update', $p->id) }}" method="POST" class="p-6 space-y-4">
                                @csrf @method('PATCH')
                                <h3 class="text-lg font-bold text-slate-900">Editar prioridade</h3>
                                <div>
                                    <label class="{{ $label }}">Título</label>
                                    <input type="text" name="titulo" value="{{ $p->titulo }}" required maxlength="150" class="{{ $input }}">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="{{ $label }}">Categoria</label>
                                        <select name="categoria" class="{{ $input }}">@foreach($categoriasPrioridade as $cat)<option @selected($p->categoria===$cat)>{{ $cat }}</option>@endforeach</select></div>
                                    <div><label class="{{ $label }}">Prioridade</label>
                                        <select name="prioridade" class="{{ $input }}">@foreach($prioVisual as $k => $pvv)<option value="{{ $k }}" @selected($p->prioridade===$k)>{{ $pvv['rotulo'] }}</option>@endforeach</select></div>
                                    <div><label class="{{ $label }}">Gasto previsto (R$)</label>
                                        <input type="number" step="0.01" min="0" name="valor_previsto" value="{{ $p->valor_previsto }}" required class="{{ $input }} font-semibold"></div>
                                    <div><label class="{{ $label }}">Gasto real (R$)</label>
                                        <input type="number" step="0.01" min="0" name="valor_gasto" value="{{ $p->valor_gasto }}" class="{{ $input }}" placeholder="ao concluir"></div>
                                    <div class="col-span-2"><label class="{{ $label }}">Prazo</label>
                                        <input type="date" name="prazo" value="{{ $p->prazo ? $p->prazo->format('Y-m-d') : '' }}" class="{{ $input }}"></div>
                                </div>
                                <div>
                                    <label class="{{ $label }}">Observações</label>
                                    <input type="text" name="observacao" value="{{ $p->observacao }}" maxlength="500" class="{{ $input }}">
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" x-on:click="$dispatch('close')" class="{{ $btnSecundario }}">Cancelar</button>
                                    <button class="{{ $btnPrimario }}">Salvar alterações</button>
                                </div>
                            </form>
                        </x-modal>
                    @empty
                        <div class="{{ $card }} p-12 text-center">
                            <p class="text-sm font-medium text-slate-500">Nenhuma prioridade cadastrada.</p>
                            <button type="button" x-on:click="$dispatch('open-modal', 'nova-prioridade')" class="mt-2 text-sm font-semibold text-slate-900 underline underline-offset-4">Montar a lista</button>
                        </div>
                    @endforelse
                </div>

                {{-- Modal: nova prioridade --}}
                <x-modal name="nova-prioridade" maxWidth="xl" :show="$formAberto === 'nova-prioridade'" focusable>
                    <form action="{{ route('potes.prioridade.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="_form" value="nova-prioridade">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Nova prioridade de investimento</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Será coberta pelo cofre de Reinvestimento.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">O que precisa ser feito?</label>
                            <input type="text" name="titulo" value="{{ old('titulo') }}" required maxlength="150" class="{{ $input }}" placeholder="Ex.: Arrumar a rampa de acesso">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="{{ $label }}">Categoria</label>
                                <select name="categoria" class="{{ $input }}">@foreach($categoriasPrioridade as $cat)<option @selected(old('categoria')===$cat)>{{ $cat }}</option>@endforeach</select></div>
                            <div><label class="{{ $label }}">Prioridade</label>
                                <select name="prioridade" class="{{ $input }}">@foreach($prioVisual as $k => $pv)<option value="{{ $k }}" @selected(old('prioridade', 'importante')===$k)>{{ $pv['rotulo'] }}</option>@endforeach</select></div>
                            <div><label class="{{ $label }}">Gasto previsto (R$)</label>
                                <input type="number" step="0.01" min="0" name="valor_previsto" value="{{ old('valor_previsto') }}" required class="{{ $input }} font-semibold" placeholder="0,00"></div>
                            <div><label class="{{ $label }}">Prazo desejado</label>
                                <input type="date" name="prazo" value="{{ old('prazo') }}" class="{{ $input }}"></div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Observações / orçamento / fornecedor</label>
                            <input type="text" name="observacao" value="{{ old('observacao') }}" maxlength="500" class="{{ $input }}" placeholder="Opcional">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" x-on:click="$dispatch('close')" class="{{ $btnSecundario }}">Cancelar</button>
                            <button class="{{ $btnPrimario }} !bg-emerald-600 hover:!bg-emerald-700">Adicionar</button>
                        </div>
                    </form>
                </x-modal>
            </div>

            {{-- ======================= CONFIGURAÇÕES ======================= --}}
            <div x-show="aba==='config'" x-cloak>
                <form action="{{ route('potes.configuracao') }}" method="POST" class="{{ $card }} overflow-hidden"
                      x-data="{ p: [{{ (float) old('pct_operacao', $config->pct_operacao) }}, {{ (float) old('pct_reinvestimento', $config->pct_reinvestimento) }}, {{ (float) old('pct_lucro', $config->pct_lucro) }}, {{ (float) old('pct_investimento', $config->pct_investimento) }}], c: [{{ (float) old('cota_socio1', $config->cota_socio1) }}, {{ (float) old('cota_socio2', $config->cota_socio2) }}, {{ (float) old('cota_socio3', $config->cota_socio3) }}] }">
                    @csrf
                    <div class="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
                        <div class="p-6">
                            <h4 class="text-sm font-bold text-slate-900">Distribuição dos potes</h4>
                            <p class="text-xs text-slate-500 mt-0.5 mb-4">Como cada faturamento é dividido. Precisa somar 100%.</p>
                            @foreach([['pct_operacao','Operação',0,'bg-sky-500'],['pct_reinvestimento','Reinvestimento',1,'bg-sky-300'],['pct_lucro','Lucro',2,'bg-amber-500'],['pct_investimento','Investimento (CDI)',3,'bg-emerald-500']] as [$n,$l,$i,$cor])
                                <div class="flex items-center justify-between gap-3 mb-2.5">
                                    <label class="flex items-center gap-2 text-sm text-slate-700"><span class="w-2 h-2 rounded-full {{ $cor }}"></span>{{ $l }}</label>
                                    <div class="relative w-28">
                                        <input type="number" step="0.01" name="{{ $n }}" x-model.number="p[{{ $i }}]" class="{{ $input }} text-right pr-7">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                    </div>
                                </div>
                            @endforeach
                            <p class="text-sm font-semibold mt-3" :class="Math.abs(p.reduce((a,b)=>a+(+b||0),0)-100) < 0.005 ? 'text-emerald-700' : 'text-rose-600'">
                                Soma: <span x-text="p.reduce((a,b)=>a+(+b||0),0).toFixed(2)"></span>%</p>
                        </div>

                        <div class="p-6">
                            <h4 class="text-sm font-bold text-slate-900">Quadro societário</h4>
                            <p class="text-xs text-slate-500 mt-0.5 mb-4">Usado para dividir o lucro. Precisa somar 100%.</p>
                            @foreach([1,2,3] as $i)
                                <div class="flex items-center gap-2 mb-2.5">
                                    <input type="text" name="nome_socio{{ $i }}" value="{{ old('nome_socio'.$i, $config->{'nome_socio'.$i}) }}" placeholder="Sócio {{ $i }}" class="{{ $input }} flex-1">
                                    <div class="relative w-24 shrink-0">
                                        <input type="number" step="0.01" name="cota_socio{{ $i }}" x-model.number="c[{{ $i-1 }}]" class="{{ $input }} text-right pr-7">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                    </div>
                                </div>
                            @endforeach
                            <p class="text-sm font-semibold mt-3" :class="Math.abs(c.reduce((a,b)=>a+(+b||0),0)-100) < 0.005 ? 'text-emerald-700' : 'text-rose-600'">
                                Soma: <span x-text="c.reduce((a,b)=>a+(+b||0),0).toFixed(2)"></span>%</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">Investimento</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Reserva separada antes dos potes e rendimento do cofre.</p>
                            </div>
                            <div>
                                <label class="{{ $label }}">Reserva livre (antes dos potes)</label>
                                <div class="flex gap-2">
                                    <select name="reserva_tipo" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                        <option value="valor" @selected(old('reserva_tipo', $config->reserva_tipo)==='valor')>R$ fixo</option>
                                        <option value="percentual" @selected(old('reserva_tipo', $config->reserva_tipo)==='percentual')>% do fat.</option>
                                    </select>
                                    <input type="number" step="0.01" min="0" name="reserva_valor" value="{{ old('reserva_valor', $config->reserva_valor) }}" class="{{ $input }} text-right">
                                </div>
                            </div>
                            <div>
                                <label class="{{ $label }}">CDI anual (%)</label>
                                <input type="number" step="0.01" min="0" name="taxa_cdi" value="{{ old('taxa_cdi', $config->taxa_cdi) }}" class="{{ $input }}">
                                <p class="text-[11px] text-slate-400 mt-1">Atualize quando a taxa mudar.</p>
                            </div>
                            <div>
                                <label class="{{ $label }}">Rende quantos % do CDI?</label>
                                <input type="number" step="0.01" min="0" name="pct_do_cdi" value="{{ old('pct_do_cdi', $config->pct_do_cdi) }}" class="{{ $input }}">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 bg-slate-50 border-t border-slate-100">
                        <p class="text-xs text-slate-500">Potes e cotas precisam fechar 100% para salvar.</p>
                        <button class="{{ $btnPrimario }} disabled:opacity-40 disabled:cursor-not-allowed"
                                :disabled="Math.abs(p.reduce((a,b)=>a+(+b||0),0)-100) >= 0.005 || Math.abs(c.reduce((a,b)=>a+(+b||0),0)-100) >= 0.005">Salvar configurações</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
