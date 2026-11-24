@php
    $nomesMeses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $fmt = fn($v) => number_format((float) $v, 2, ',', '.');
    $card = 'bg-white rounded-2xl ring-1 ring-slate-200 shadow-sm';

    $hora = (int) now()->format('H');
    $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
    $primeiroNome = explode(' ', trim(auth()->user()->name ?? ''))[0] ?: 'tudo bem';

    $semMovimento = $receitasMes == 0 && $despesasMes == 0;

    $atalhos = [
        ['Importar extratos', 'Bancos e maquininhas', route('importacao.index'), 'bg-sky-50 text-sky-700',
            'M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12l-4 4m4-4l4 4'],
        ['DRE', 'Resultado e fluxo de caixa', route('dre.index'), 'bg-emerald-50 text-emerald-700',
            'M4 19h16M7 16V9m5 7V5m5 11v-4'],
        ['Despesas e potes', 'Despesas, cofres e prioridades', route('despesas.index'), 'bg-amber-50 text-amber-700',
            'M3 7h18v12H3zM3 7l2-3h14l2 3M9 12h6'],
        ['Configurações', 'Saldos iniciais e taxas', route('configuracoes.index'), 'bg-slate-100 text-slate-700',
            'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Painel</h2>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 tabular-nums">

            {{-- BOAS-VINDAS --}}
            <div class="rounded-2xl bg-slate-900 text-white p-6 sm:p-8 shadow-sm flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $nomesMeses[(int) $mesAtual] }} de {{ $anoAtual }}</p>
                    <h3 class="text-2xl sm:text-3xl font-bold mt-1">{{ $saudacao }}, {{ $primeiroNome }}</h3>
                    <p class="text-sm text-slate-400 mt-2">
                        @if($semMovimento)
                            Nenhuma movimentação bancária importada neste mês ainda.
                        @elseif($resultadoMes >= 0)
                            Neste mês entrou mais dinheiro do que saiu nos bancos.
                        @else
                            Atenção: neste mês saiu mais dinheiro do que entrou nos bancos.
                        @endif
                    </p>
                </div>
                @unless($semMovimento)
                    <div class="sm:text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Resultado do mês</p>
                        <p class="text-2xl font-bold mt-1 {{ $resultadoMes >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                            {{ $resultadoMes >= 0 ? '+' : '−' }} R$ {{ $fmt(abs($resultadoMes)) }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Atualizado em {{ now()->format('d/m/Y \à\s H:i') }}</p>
                    </div>
                @endunless
            </div>

            {{-- MÉTRICAS --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="{{ $card }} p-5 border-t-4 border-t-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Saldo atual</p>
                    <p class="text-2xl font-bold mt-2 {{ $saldoAtual < 0 ? 'text-rose-600' : 'text-slate-900' }}">R$ {{ $fmt($saldoAtual) }}</p>
                    <p class="text-xs mt-2 {{ $temSaldoInicial ? 'text-slate-500' : 'text-amber-700' }}">
                        @if($temSaldoInicial)
                            Saldo inicial + entradas − saídas
                        @else
                            Sem saldo inicial do mês: <a href="{{ route('configuracoes.index') }}" class="font-semibold underline underline-offset-2">cadastrar</a>
                        @endif
                    </p>
                </div>
                <div class="{{ $card }} p-5 border-t-4 border-t-emerald-500">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Receitas do mês</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($receitasMes) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Entradas nos extratos bancários</p>
                </div>
                <div class="{{ $card }} p-5 border-t-4 border-t-rose-500">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Despesas do mês</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($despesasMes) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Saídas nos extratos bancários</p>
                </div>
                <div class="{{ $card }} p-5 border-t-4 border-t-violet-500">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">A receber da maquininha</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ $fmt($aReceberMaquininha) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Vendas de {{ $anoAtual }} ainda não depositadas (bruto)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- ÚLTIMAS MOVIMENTAÇÕES --}}
                <div class="{{ $card }} lg:col-span-2 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Últimas movimentações</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Lançamentos mais recentes dos extratos bancários.</p>
                        </div>
                        <a href="{{ route('importacao.index') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Ver tudo &rarr;</a>
                    </div>

                    @if($ultimasTransacoes->isEmpty())
                        <div class="px-6 py-14 text-center">
                            <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-700">Nenhuma movimentação ainda</p>
                            <p class="mt-1 text-sm text-slate-500">Importe um extrato do Bradesco ou do Itaú para ver os lançamentos aqui.</p>
                            <a href="{{ route('importacao.index') }}" class="mt-5 inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">Importar extrato</a>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach($ultimasTransacoes as $t)
                                @php $entrada = $t->tipo === 'entrada'; @endphp
                                <li class="flex items-center gap-4 px-6 py-3.5 hover:bg-slate-50 transition">
                                    <span class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center {{ $entrada ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            @if($entrada)
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 14l-6-6m6 6l6-6"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0-14l-6 6m6-6l6 6"/>
                                            @endif
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-900 truncate" title="{{ $t->descricao }}">{{ $t->descricao }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ \Carbon\Carbon::parse($t->data_transacao)->format('d/m/Y') }}
                                            &middot; {{ ['bradesco' => 'Bradesco', 'itau' => 'Itaú'][$t->conta_instituicao] ?? ucfirst((string) $t->conta_instituicao) }}
                                            @if($t->categoria) &middot; {{ $t->categoria }} @endif
                                        </p>
                                    </div>
                                    <p class="shrink-0 text-sm font-semibold {{ $entrada ? 'text-emerald-700' : 'text-rose-600' }}">
                                        {{ $entrada ? '+' : '−' }} R$ {{ $fmt($t->valor) }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- ACESSO RÁPIDO --}}
                <div class="{{ $card }} p-6">
                    <h3 class="text-base font-bold text-slate-900">Acesso rápido</h3>
                    <p class="text-xs text-slate-500 mt-0.5 mb-4">Atalhos para as rotinas do dia a dia.</p>
                    <div class="space-y-2">
                        @foreach($atalhos as [$titulo, $descricao, $url, $tom, $icone])
                            <a href="{{ $url }}" class="group flex items-center gap-3 rounded-xl p-3 ring-1 ring-slate-200 hover:ring-slate-300 hover:bg-slate-50 transition">
                                <span class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $tom }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icone }}"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $titulo }}</span>
                                    <span class="block text-xs text-slate-500">{{ $descricao }}</span>
                                </span>
                                <span class="text-slate-300 group-hover:text-slate-600 group-hover:translate-x-0.5 transition">&rarr;</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
