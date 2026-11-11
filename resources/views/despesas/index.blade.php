@php
    $br = fn($v) => 'R$ ' . number_format($v ?? 0, 2, ',', '.');
    $meses = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];

    // Visual de cada cofre (classes completas para o Tailwind enxergar)
    $visual = [
        'reinvestimento' => ['titulo' => '🛒 Reinvestimento', 'desc' => 'Acervo, compras e marketing', 'card' => 'border-indigo-200 bg-indigo-50', 'texto' => 'text-indigo-700', 'btn' => 'bg-indigo-600 hover:bg-indigo-700', 'rotulo' => 'Registrar uso do fundo', 'campo' => 'No que foi gasto?'],
        'lucro'          => ['titulo' => '💰 Lucro dos sócios', 'desc' => 'Dividendos', 'card' => 'border-amber-200 bg-amber-50', 'texto' => 'text-amber-700', 'btn' => 'bg-amber-500 hover:bg-amber-600', 'rotulo' => 'Distribuir lucro', 'campo' => 'Quem recebeu?'],
        'investimento'   => ['titulo' => '📈 Investimento (CDI)', 'desc' => 'Reserva + pote de investimento', 'card' => 'border-emerald-200 bg-emerald-50', 'texto' => 'text-emerald-700', 'btn' => 'bg-emerald-600 hover:bg-emerald-700', 'rotulo' => 'Registrar resgate', 'campo' => 'Motivo do resgate'],
    ];
    $totalGuardado = collect($cofres)->sum('saldo');
    $input = 'border-gray-300 rounded-md text-sm w-full shadow-sm';

    // ---- Prioridades de investimento da empresa ----
    $prioridades = $prioridades ?? collect();
    $prioVisual = [
        'urgente'      => ['rotulo' => '🔴 Urgente',      'borda' => 'border-l-red-500',     'badge' => 'bg-red-50 text-red-700 border-red-200'],
        'importante'   => ['rotulo' => '🟠 Importante',   'borda' => 'border-l-orange-400',  'badge' => 'bg-orange-50 text-orange-700 border-orange-200'],
        'pode_esperar' => ['rotulo' => '🟢 Pode esperar', 'borda' => 'border-l-emerald-400', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
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
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Planejamento Financeiro</h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen"
         x-data="{ aba: (['painel','despesas','cofres','config','prioridades'].includes(localStorage.getItem('despAba')) ? localStorage.getItem('despAba') : 'painel') }"
         x-init="$watch('aba', v => localStorage.setItem('despAba', v))">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Mensagens --}}
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Barra: abas + seletor de período --}}
            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div class="bg-white rounded-lg p-1 shadow-sm inline-flex border border-gray-200 flex-wrap">
                    @foreach(['painel' => '📊 Painel', 'despesas' => '🧾 Despesas do mês', 'cofres' => '🏦 Cofres', 'config' => '⚙️ Configurações', 'prioridades' => '🎯 Prioridades de investimento'] as $k => $r)
                        <button @click="aba='{{ $k }}'" :class="aba==='{{ $k }}' ? 'bg-gray-800 text-white shadow' : 'text-gray-600 hover:text-gray-900'"
                                class="px-4 py-2 rounded-md text-sm font-bold transition">{{ $r }}</button>
                    @endforeach
                </div>
                <form method="GET" class="flex gap-2 items-center">
                    <select name="mes" onchange="this.form.submit()" class="border-gray-300 rounded-md text-sm shadow-sm">
                        @foreach($meses as $n => $m)<option value="{{ $n }}" @selected($n==$mes)>{{ $m }}</option>@endforeach
                    </select>
                    <input type="number" name="ano" value="{{ $ano }}" onchange="this.form.submit()" class="border-gray-300 rounded-md text-sm shadow-sm w-24">
                </form>
            </div>

            {{-- ======================= PAINEL ======================= --}}
            <div x-show="aba==='painel'" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl border p-5">
                        <p class="text-xs font-bold uppercase text-gray-400">Faturamento {{ $meses[$mes] }}/{{ $ano }}</p>
                        <p class="text-2xl font-black text-gray-900">{{ $br($faturamentoMes->faturamento_informado ?? 0) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border p-5">
                        <p class="text-xs font-bold uppercase text-gray-400">Verba da operação</p>
                        <p class="text-2xl font-black text-blue-800">{{ $br($faturamentoMes->valor_operacao ?? 0) }}</p>
                        <p class="text-xs {{ $saldoOperacao >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">Sobra: {{ $br($saldoOperacao) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border p-5">
                        <p class="text-xs font-bold uppercase text-gray-400">Despesas do mês</p>
                        <p class="text-2xl font-black text-red-600">{{ $br($totalDespesas) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border p-5">
                        <p class="text-xs font-bold uppercase text-gray-400">Total guardado nos cofres</p>
                        <p class="text-2xl font-black text-emerald-700">{{ $br($totalGuardado) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Entrada do mês --}}
                    <div class="bg-white rounded-xl border p-6">
                        <h3 class="font-black text-gray-800">Entrada do mês</h3>
                        <p class="text-xs text-gray-500 mb-4">Informe o faturamento líquido. A reserva de investimento sai primeiro; o resto é dividido nos potes.</p>
                        <form action="{{ route('potes.faturamento') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="mes" value="{{ $mes }}">
                            <input type="hidden" name="ano" value="{{ $ano }}">
                            <input type="number" step="0.01" min="0" name="faturamento_informado" required
                                   value="{{ $faturamentoMes->faturamento_informado ?? '' }}" placeholder="0,00"
                                   class="{{ $input }} font-bold text-green-700 bg-green-50 text-lg">
                            <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded">Distribuir nos potes</button>
                        </form>
                        @if($faturamentoMes)
                            <dl class="mt-4 text-sm space-y-1">
                                <div class="flex justify-between"><dt>Reserva de investimento</dt><dd class="font-bold">{{ $br($faturamentoMes->valor_reserva ?? 0) }}</dd></div>
                                <div class="flex justify-between"><dt>Operação ({{ $config->pct_operacao }}%)</dt><dd class="font-bold">{{ $br($faturamentoMes->valor_operacao) }}</dd></div>
                                <div class="flex justify-between"><dt>Reinvestimento ({{ $config->pct_reinvestimento }}%)</dt><dd class="font-bold">{{ $br($faturamentoMes->valor_reinvestimento) }}</dd></div>
                                <div class="flex justify-between"><dt>Lucro ({{ $config->pct_lucro }}%)</dt><dd class="font-bold">{{ $br($faturamentoMes->valor_lucro) }}</dd></div>
                                <div class="flex justify-between"><dt>Investimento ({{ $config->pct_investimento }}% + reserva)</dt><dd class="font-bold">{{ $br($faturamentoMes->valor_investimento ?? 0) }}</dd></div>
                            </dl>
                        @endif
                    </div>

                    {{-- Termômetro --}}
                    <div class="bg-white rounded-xl border p-6 md:col-span-2">
                        <h3 class="font-black text-gray-800">🌡️ Termômetro de despesas fixas</h3>
                        <p class="text-xs text-gray-500 mb-4">Compara as despesas fixas deste mês com o mês anterior para flagrar aumento silencioso de custo.</p>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div><p class="text-xs uppercase text-gray-400 font-bold">Mês anterior</p><p class="text-xl font-black">{{ $br($fixasAnt) }}</p></div>
                            <div><p class="text-xs uppercase text-gray-400 font-bold">Este mês</p><p class="text-xl font-black">{{ $br($totalFixas) }}</p></div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-bold">Variação</p>
                                @if(is_null($variacaoFixas))
                                    <p class="text-xl font-black text-gray-400">sem base</p>
                                @elseif($variacaoFixas > 0)
                                    <p class="text-xl font-black text-red-600">🔴 +{{ $variacaoFixas }}%</p>
                                @elseif($variacaoFixas < 0)
                                    <p class="text-xl font-black text-green-600">🟢 {{ $variacaoFixas }}%</p>
                                @else
                                    <p class="text-xl font-black text-gray-600">⚪ estável</p>
                                @endif
                            </div>
                        </div>
                        @if($rendimentoEstimado > 0)
                            <p class="mt-4 text-sm text-emerald-700 bg-emerald-50 rounded p-3">📈 Rendimento estimado do cofre de investimento: <b>{{ $br($rendimentoEstimado) }}/mês</b> ({{ $config->pct_do_cdi }}% do CDI a {{ $config->taxa_cdi }}% a.a.)</p>
                        @endif
                    </div>
                </div>

                {{-- Histórico --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl border p-4">
                        <h4 class="font-bold text-gray-700 text-sm mb-3">Histórico de faturamentos</h4>
                        <div class="overflow-y-auto max-h-64">
                            <table class="w-full text-xs text-left">
                                <thead class="text-gray-400 border-b"><tr><th>Mês</th><th>Faturamento</th><th>Operação</th><th>Reinv.</th><th>Lucro</th><th>Invest.</th></tr></thead>
                                <tbody>
                                @forelse($historicoFaturamentos as $h)
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1">{{ str_pad($h->mes,2,'0',STR_PAD_LEFT) }}/{{ $h->ano }}</td>
                                        <td class="font-bold">{{ $br($h->faturamento_informado) }}</td>
                                        <td>{{ $br($h->valor_operacao) }}</td>
                                        <td>{{ $br($h->valor_reinvestimento) }}</td>
                                        <td>{{ $br($h->valor_lucro) }}</td>
                                        <td>{{ $br($h->valor_investimento ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-3 text-gray-400">Nenhum faturamento registrado.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border p-4">
                        <h4 class="font-bold text-gray-700 text-sm mb-3">Log de saques</h4>
                        <div class="overflow-y-auto max-h-64">
                            <table class="w-full text-xs text-left">
                                <thead class="text-gray-400 border-b"><tr><th>Data</th><th>Cofre</th><th>Quem / finalidade</th><th class="text-right">Valor</th></tr></thead>
                                <tbody>
                                @forelse($historicoSaques as $s)
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1">{{ \Carbon\Carbon::parse($s->data_saque)->format('d/m/Y') }}</td>
                                        <td class="uppercase font-bold text-[10px] {{ $visual[$s->tipo_pote]['texto'] ?? '' }}">{{ $s->tipo_pote }}</td>
                                        <td>{{ $s->descricao_beneficiario }}</td>
                                        <td class="text-right text-red-600 font-bold">-{{ $br($s->valor) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-3 text-gray-400">Nenhum saque realizado.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================= DESPESAS ======================= --}}
            <div x-show="aba==='despesas'" x-cloak class="bg-white rounded-xl border p-6 space-y-4">
                <div class="flex flex-wrap justify-between items-center gap-2">
                    <div>
                        <h3 class="font-black text-gray-800">Despesas de {{ $meses[$mes] }}/{{ $ano }}</h3>
                        <p class="text-xs text-gray-500">Fixas = todo mês · Parceladas geram uma linha em cada mês futuro automaticamente.</p>
                    </div>
                    <form action="{{ route('potes.fixas.copiar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="mes" value="{{ $mes }}"><input type="hidden" name="ano" value="{{ $ano }}">
                        <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold px-3 py-2 rounded border">↻ Copiar fixas do mês anterior</button>
                    </form>
                </div>

                <form action="{{ route('potes.despesa') }}" method="POST" x-data="{ rec: 'unica' }"
                      class="bg-gray-50 p-3 rounded border grid grid-cols-2 md:grid-cols-6 gap-2 items-end">
                    @csrf
                    <div><label class="text-[10px] font-bold text-gray-500 uppercase">Data</label>
                        <input type="date" name="data_despesa" value="{{ now()->setYear($ano)->setMonth($mes)->format('Y-m-d') }}" required class="{{ $input }}"></div>
                    <div class="md:col-span-2"><label class="text-[10px] font-bold text-gray-500 uppercase">Descrição</label>
                        <input type="text" name="descricao" required class="{{ $input }}" placeholder="Ex: Aluguel, fornecedor..."></div>
                    <div><label class="text-[10px] font-bold text-gray-500 uppercase">Tipo</label>
                        <select name="recorrencia" x-model="rec" class="{{ $input }}">
                            <option value="unica">Única</option><option value="fixa">Fixa (todo mês)</option><option value="parcelada">Parcelada</option>
                        </select></div>
                    <div><label class="text-[10px] font-bold text-gray-500 uppercase">Valor (R$)</label>
                        <input type="number" step="0.01" name="valor" required class="{{ $input }} font-bold"></div>
                    <div class="flex gap-2 items-end">
                        <input x-show="rec==='parcelada'" x-cloak type="number" name="total_parcelas" min="2" placeholder="Nº parc." class="{{ $input }}">
                        <button class="bg-gray-800 hover:bg-black text-white px-4 py-2 rounded text-sm font-bold">Adicionar</button>
                    </div>
                </form>

                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-500">
                        <tr><th class="px-3 py-2">Data</th><th class="px-3 py-2">Descrição</th><th class="px-3 py-2">Tipo</th><th class="px-3 py-2 text-right">Valor</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($despesas as $d)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($d->data_despesa)->format('d/m') }}</td>
                            <td class="px-3 py-2 font-medium">{{ $d->descricao }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ ['unica'=>'Única','fixa'=>'Fixa','parcelada'=>'Parcelada'][$d->recorrencia] ?? $d->recorrencia }}</td>
                            <td class="px-3 py-2 text-right font-bold text-red-600">{{ $br($d->valor) }}</td>
                            <td class="px-3 py-2 text-center">
                                <form action="{{ route('potes.despesa.destroy', $d->id) }}" method="POST" onsubmit="return confirm('Excluir esta despesa?')">
                                    @csrf @method('DELETE')<button class="text-red-500 hover:text-red-700 text-xs font-bold">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-6 text-gray-400 italic">Nenhuma despesa neste mês.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot class="font-bold">
                        <tr><td colspan="3" class="px-3 py-2 text-right text-gray-500 uppercase text-xs">Fixas</td><td class="px-3 py-2 text-right">{{ $br($totalFixas) }}</td><td></td></tr>
                        <tr><td colspan="3" class="px-3 py-2 text-right text-gray-500 uppercase text-xs">Total consumido da operação</td><td class="px-3 py-2 text-right text-red-600">{{ $br($totalDespesas) }}</td><td></td></tr>
                    </tfoot>
                </table>
            </div>

            {{-- ======================= COFRES ======================= --}}
            <div x-show="aba==='cofres'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                @foreach($cofres as $chave => $c)
                    @php $v = $visual[$chave]; @endphp
                    <div class="rounded-xl border {{ $v['card'] }} p-5 space-y-3" x-data="{ aberto: false }">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-black {{ $v['texto'] }}">{{ $v['titulo'] }} ({{ $c['pct'] }}%)</h3>
                                <p class="text-xs text-gray-500">{{ $v['desc'] }}</p>
                            </div>
                            @if($c['ciclo'])
                                <span class="text-[10px] font-black px-2 py-1 rounded {{ $c['aberto'] ? 'bg-green-500 text-white' : 'bg-white text-gray-500 border' }}">
                                    {{ $c['aberto'] ? 'RESGATE ABERTO' : 'Aguardando ciclo' }}
                                </span>
                            @endif
                        </div>

                        @if($c['ciclo'])
                            <div class="flex gap-1">
                                @foreach($meses as $n => $m)
                                    <span class="flex-1 text-center text-[9px] font-bold rounded py-0.5 {{ in_array($n, $c['ciclo']) ? ($n==$mes ? 'bg-green-500 text-white' : 'bg-gray-800 text-white') : 'bg-white text-gray-300' }}">{{ $m }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="text-center bg-white rounded-lg py-5 shadow-sm">
                            <p class="text-xs font-bold text-gray-500 uppercase">Saldo acumulado</p>
                            <p class="text-3xl font-black {{ $v['texto'] }}">{{ $br($c['saldo']) }}</p>
                            @if($chave === 'investimento' && $rendimentoEstimado > 0)
                                <p class="text-xs text-emerald-600 font-bold">≈ {{ $br($rendimentoEstimado) }}/mês de rendimento</p>
                            @endif
                        </div>

                        @if($chave === 'lucro')
                            <div class="bg-white rounded p-3 text-xs space-y-1 font-mono">
                                <p class="font-bold uppercase text-amber-700 font-sans">Projeção com o saldo atual</p>
                                @foreach($socios as $s)
                                    <p class="flex justify-between"><span>{{ $s['nome'] }} ({{ $s['cota'] }}%)</span><span>{{ $br($c['saldo'] * $s['cota'] / 100) }}</span></p>
                                @endforeach
                            </div>
                        @endif

                        <button @click="aberto=!aberto" class="w-full {{ $v['btn'] }} text-white font-bold py-2 rounded">{{ $v['rotulo'] }}</button>

                        <form x-show="aberto" x-cloak action="{{ route('potes.saque') }}" method="POST" class="bg-white rounded p-3 space-y-2">
                            @csrf
                            <input type="hidden" name="tipo_pote" value="{{ $chave }}">
                            <div><label class="text-[10px] font-bold uppercase text-gray-500">Valor (R$)</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $c['saldo'] }}" name="valor" value="{{ $chave==='lucro' ? $c['saldo'] : '' }}" required class="{{ $input }} font-bold"></div>
                            @if($chave === 'lucro')
                                <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="distribuir" value="1" checked> Dividir automaticamente pelas cotas dos sócios</label>
                            @endif
                            <div><label class="text-[10px] font-bold uppercase text-gray-500">{{ $chave==='lucro' ? 'Beneficiário (se não dividir)' : $v['campo'] }}</label>
                                <input type="text" name="descricao_beneficiario" class="{{ $input }}"></div>
                            <div><label class="text-[10px] font-bold uppercase text-gray-500">Data</label>
                                <input type="date" name="data_saque" value="{{ now()->format('Y-m-d') }}" required class="{{ $input }}"></div>
                            <button class="w-full bg-gray-800 hover:bg-black text-white font-bold py-2 rounded text-sm">Confirmar</button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{-- ======================= CONFIGURAÇÕES ======================= --}}
            <div x-show="aba==='config'" x-cloak class="bg-white rounded-xl border p-6">
                <form action="{{ route('potes.configuracao') }}" method="POST" class="space-y-6"
                      x-data="{ p: [{{ $config->pct_operacao }}, {{ $config->pct_reinvestimento }}, {{ $config->pct_lucro }}, {{ $config->pct_investimento }}], c: [{{ $config->cota_socio1 }}, {{ $config->cota_socio2 }}, {{ $config->cota_socio3 }}] }">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <h4 class="font-bold text-sm uppercase text-gray-500 mb-3 border-b pb-2">Distribuição dos potes (%)</h4>
                            @foreach([['pct_operacao','Operação',0],['pct_reinvestimento','Reinvestimento',1],['pct_lucro','Lucro',2],['pct_investimento','Investimento (CDI)',3]] as [$n,$l,$i])
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-bold text-gray-700">{{ $l }}</label>
                                    <input type="number" step="0.01" name="{{ $n }}" x-model.number="p[{{ $i }}]" class="w-24 text-right border-gray-300 rounded shadow-sm">
                                </div>
                            @endforeach
                            <p class="text-sm font-black mt-2" :class="Math.abs(p.reduce((a,b)=>a+(+b||0),0)-100) < 0.005 ? 'text-green-600' : 'text-red-600'">
                                Soma: <span x-text="p.reduce((a,b)=>a+(+b||0),0).toFixed(2)"></span>%</p>
                        </div>

                        <div>
                            <h4 class="font-bold text-sm uppercase text-gray-500 mb-3 border-b pb-2">Quadro societário (%)</h4>
                            @foreach([1,2,3] as $i)
                                <div class="flex items-center gap-2 mb-2">
                                    <input type="text" name="nome_socio{{ $i }}" value="{{ $config->{'nome_socio'.$i} }}" placeholder="Sócio {{ $i }}" class="border-gray-300 rounded text-sm flex-1 shadow-sm">
                                    <input type="number" step="0.01" name="cota_socio{{ $i }}" x-model.number="c[{{ $i-1 }}]" class="w-20 text-right border-gray-300 rounded shadow-sm">
                                </div>
                            @endforeach
                            <p class="text-sm font-black mt-2" :class="Math.abs(c.reduce((a,b)=>a+(+b||0),0)-100) < 0.005 ? 'text-green-600' : 'text-red-600'">
                                Soma: <span x-text="c.reduce((a,b)=>a+(+b||0),0).toFixed(2)"></span>%</p>
                        </div>

                        <div>
                            <h4 class="font-bold text-sm uppercase text-gray-500 mb-3 border-b pb-2">Investimento</h4>
                            <label class="text-xs font-bold text-gray-600">Reserva livre (antes dos potes)</label>
                            <div class="flex gap-2 mb-3">
                                <select name="reserva_tipo" class="border-gray-300 rounded text-sm shadow-sm">
                                    <option value="valor" @selected($config->reserva_tipo==='valor')>R$ fixo</option>
                                    <option value="percentual" @selected($config->reserva_tipo==='percentual')>% do fat.</option>
                                </select>
                                <input type="number" step="0.01" min="0" name="reserva_valor" value="{{ $config->reserva_valor }}" class="w-full border-gray-300 rounded shadow-sm text-right">
                            </div>
                            <label class="text-xs font-bold text-gray-600">CDI anual (%) — você atualiza</label>
                            <input type="number" step="0.01" min="0" name="taxa_cdi" value="{{ $config->taxa_cdi }}" class="{{ $input }} mb-3">
                            <label class="text-xs font-bold text-gray-600">Rende quantos % do CDI?</label>
                            <input type="number" step="0.01" min="0" name="pct_do_cdi" value="{{ $config->pct_do_cdi }}" class="{{ $input }}">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button class="bg-gray-800 hover:bg-black text-white px-6 py-2 rounded font-bold disabled:opacity-40"
                                :disabled="Math.abs(p.reduce((a,b)=>a+(+b||0),0)-100) >= 0.005 || Math.abs(c.reduce((a,b)=>a+(+b||0),0)-100) >= 0.005">Salvar (exige 100%)</button>
                    </div>
                </form>
            </div>

            {{-- ======================= PRIORIDADES DE INVESTIMENTO ======================= --}}
            <div x-show="aba==='prioridades'" x-cloak class="space-y-6"
                 x-data="{ filtro: 'abertas', novo: {{ $errors->any() ? 'true' : 'false' }} }">

                {{-- Resumo --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl border p-5">
                        <p class="text-xs font-bold uppercase text-gray-400">Previsto em aberto</p>
                        <p class="text-2xl font-black text-gray-900">{{ $br($totalPrevisto) }}</p>
                        <p class="text-xs text-gray-500 font-bold">{{ $abertas->count() }} {{ $abertas->count() === 1 ? 'item' : 'itens' }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-red-200 p-5">
                        <p class="text-xs font-bold uppercase text-red-400">Urgente</p>
                        <p class="text-2xl font-black text-red-600">{{ $br($totalUrgente) }}</p>
                        <p class="text-xs text-red-500 font-bold">{{ $qtdUrgente }} {{ $qtdUrgente === 1 ? 'item' : 'itens' }}</p>
                    </div>
                    <div class="bg-white rounded-xl border p-5">
                        <p class="text-xs font-bold uppercase text-gray-400">Em andamento</p>
                        <p class="text-2xl font-black text-blue-700">{{ $qtdAndamento }}</p>
                        <p class="text-xs text-gray-500 font-bold">{{ $br($totalAndamento) }} previstos</p>
                    </div>
                    <div class="bg-white rounded-xl border p-5">
                        <p class="text-xs font-bold uppercase text-gray-400">Já investido (concluídos)</p>
                        <p class="text-2xl font-black text-emerald-700">{{ $br($totalGasto) }}</p>
                        <p class="text-xs text-gray-500 font-bold">{{ $prioridades->where('status','concluido')->count() }} concluídos</p>
                    </div>
                </div>

                {{-- Cobertura pelo cofre de Reinvestimento --}}
                <div class="bg-white rounded-xl border p-5">
                    <div class="flex flex-wrap justify-between items-end gap-2 mb-2">
                        <div>
                            <h3 class="font-black text-gray-800">Cobertura pelo cofre de Reinvestimento</h3>
                            <p class="text-xs text-gray-500">Saldo atual do cofre comparado ao total previsto das prioridades em aberto.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase font-bold text-gray-400">Saldo do cofre</p>
                            <p class="text-xl font-black text-indigo-700">{{ $br($saldoReinv) }}</p>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                        <div class="h-3 rounded-full {{ $cobertura >= 0 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $pctCobertura }}%"></div>
                    </div>
                    <p class="mt-2 text-sm font-bold {{ $cobertura >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                        @if($totalPrevisto <= 0)
                            Nenhuma prioridade em aberto.
                        @elseif($cobertura >= 0)
                            O cofre cobre {{ $pctCobertura }}% — sobram {{ $br($cobertura) }} depois de realizar tudo.
                        @else
                            O cofre cobre {{ $pctCobertura }}% — faltam {{ $br(abs($cobertura)) }} para realizar tudo.
                        @endif
                    </p>
                </div>

                {{-- Barra de filtros + novo --}}
                <div class="flex flex-wrap gap-3 items-center justify-between">
                    <div class="bg-white rounded-lg p-1 shadow-sm inline-flex border border-gray-200 flex-wrap">
                        @foreach(['abertas' => 'Em aberto', 'pendente' => 'Pendentes', 'em_andamento' => 'Em andamento', 'concluido' => 'Concluídas', 'todas' => 'Todas'] as $k => $r)
                            <button type="button" @click="filtro='{{ $k }}'" :class="filtro==='{{ $k }}' ? 'bg-gray-800 text-white shadow' : 'text-gray-600 hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-md text-xs font-bold transition">{{ $r }}</button>
                        @endforeach
                    </div>
                    <button type="button" @click="novo=!novo" class="bg-gray-800 hover:bg-black text-white text-sm font-bold px-4 py-2 rounded">
                        <span x-show="!novo">+ Nova prioridade</span><span x-show="novo" x-cloak>Fechar</span>
                    </button>
                </div>

                {{-- Formulário de nova prioridade --}}
                <form x-show="novo" x-cloak action="{{ route('potes.prioridade.store') }}" method="POST"
                      class="bg-white rounded-xl border p-5 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                    @csrf
                    <div class="md:col-span-3">
                        <label class="text-[10px] font-bold text-gray-500 uppercase">O que precisa ser feito?</label>
                        <input type="text" name="titulo" required maxlength="150" class="{{ $input }}" placeholder="Ex: Arrumar a rampa de acesso">
                    </div>
                    <div class="md:col-span-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase">Categoria</label>
                        <select name="categoria" class="{{ $input }}">
                            @foreach($categoriasPrioridade as $cat)<option>{{ $cat }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase">Prioridade</label>
                        <select name="prioridade" class="{{ $input }}">
                            @foreach($prioVisual as $k => $pv)<option value="{{ $k }}" @selected($k==='importante')>{{ $pv['rotulo'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase">Gasto previsto (R$)</label>
                        <input type="number" step="0.01" min="0" name="valor_previsto" required class="{{ $input }} font-bold" placeholder="0,00">
                    </div>
                    <div class="md:col-span-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase">Prazo desejado</label>
                        <input type="date" name="prazo" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-4">
                        <label class="text-[10px] font-bold text-gray-500 uppercase">Observações / orçamento / fornecedor</label>
                        <input type="text" name="observacao" maxlength="500" class="{{ $input }}" placeholder="Opcional">
                    </div>
                    <div class="md:col-span-1">
                        <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded text-sm">Adicionar</button>
                    </div>
                </form>

                {{-- Lista --}}
                <div class="space-y-3">
                    @forelse($prioridadesOrdenadas as $p)
                        @php
                            $pv = $prioVisual[$p->prioridade] ?? $prioVisual['importante'];
                            $concluido = $p->status === 'concluido';
                            $atrasado = $p->prazo && !$concluido && $p->prazo->isPast() && !$p->prazo->isToday();
                        @endphp
                        <div x-data="{ edit: false }"
                             x-show="filtro==='todas' || (filtro==='abertas' && '{{ $p->status }}' !== 'concluido') || filtro==='{{ $p->status }}'"
                             class="bg-white rounded-xl border border-l-4 {{ $pv['borda'] }} p-4 {{ $concluido ? 'opacity-60' : '' }}">

                            <div class="flex flex-wrap justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded border {{ $pv['badge'] }}">{{ $pv['rotulo'] }}</span>
                                        @if($p->categoria)<span class="text-[10px] font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-600 uppercase">{{ $p->categoria }}</span>@endif
                                        @if($p->status === 'em_andamento')<span class="text-[10px] font-black px-2 py-0.5 rounded bg-blue-100 text-blue-700 uppercase">Em andamento</span>@endif
                                        @if($concluido)<span class="text-[10px] font-black px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 uppercase">✔ Concluído</span>@endif
                                        @if($atrasado)<span class="text-[10px] font-black px-2 py-0.5 rounded bg-red-600 text-white uppercase">Prazo vencido</span>@endif
                                    </div>
                                    <p class="font-bold text-gray-900 {{ $concluido ? 'line-through' : '' }}">{{ $p->titulo }}</p>
                                    @if($p->observacao)<p class="text-xs text-gray-500 mt-0.5">{{ $p->observacao }}</p>@endif
                                    @if($p->prazo)<p class="text-xs mt-1 {{ $atrasado ? 'text-red-600 font-bold' : 'text-gray-400' }}">📅 Prazo: {{ $p->prazo->format('d/m/Y') }}</p>@endif
                                </div>

                                <div class="text-right">
                                    <p class="text-[10px] uppercase font-bold text-gray-400">{{ $concluido ? 'Valor gasto' : 'Gasto previsto' }}</p>
                                    <p class="text-xl font-black {{ $concluido ? 'text-emerald-700' : 'text-gray-900' }}">{{ $br($concluido ? ($p->valor_gasto ?? $p->valor_previsto) : $p->valor_previsto) }}</p>
                                    @if($concluido && $p->valor_gasto !== null && (float)$p->valor_gasto !== (float)$p->valor_previsto)
                                        <p class="text-[10px] font-bold {{ $p->valor_gasto > $p->valor_previsto ? 'text-red-600' : 'text-emerald-600' }}">previsto {{ $br($p->valor_previsto) }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                                <form action="{{ route('potes.prioridade.update', $p->id) }}" method="POST" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <label class="text-[10px] font-bold text-gray-400 uppercase">Status</label>
                                    <select name="status" onchange="this.form.submit()" class="border-gray-300 rounded-md text-xs shadow-sm py-1">
                                        @foreach($statusVisual as $sk => $sr)<option value="{{ $sk }}" @selected($p->status===$sk)>{{ $sr }}</option>@endforeach
                                    </select>
                                </form>
                                <button type="button" @click="edit=!edit" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">✏️ Editar</button>
                                <form action="{{ route('potes.prioridade.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Excluir esta prioridade?')" class="ml-auto">
                                    @csrf @method('DELETE')<button class="text-red-500 hover:text-red-700 text-xs font-bold">Excluir</button>
                                </form>
                            </div>

                            {{-- Edição --}}
                            <form x-show="edit" x-cloak action="{{ route('potes.prioridade.update', $p->id) }}" method="POST"
                                  class="mt-3 bg-gray-50 border rounded p-3 grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                                @csrf @method('PATCH')
                                <div class="md:col-span-3"><label class="text-[10px] font-bold text-gray-500 uppercase">Título</label>
                                    <input type="text" name="titulo" value="{{ $p->titulo }}" required maxlength="150" class="{{ $input }}"></div>
                                <div><label class="text-[10px] font-bold text-gray-500 uppercase">Categoria</label>
                                    <select name="categoria" class="{{ $input }}">
                                        @foreach($categoriasPrioridade as $cat)<option @selected($p->categoria===$cat)>{{ $cat }}</option>@endforeach
                                    </select></div>
                                <div><label class="text-[10px] font-bold text-gray-500 uppercase">Prioridade</label>
                                    <select name="prioridade" class="{{ $input }}">
                                        @foreach($prioVisual as $k => $pvv)<option value="{{ $k }}" @selected($p->prioridade===$k)>{{ $pvv['rotulo'] }}</option>@endforeach
                                    </select></div>
                                <div><label class="text-[10px] font-bold text-gray-500 uppercase">Prazo</label>
                                    <input type="date" name="prazo" value="{{ $p->prazo ? $p->prazo->format('Y-m-d') : '' }}" class="{{ $input }}"></div>
                                <div><label class="text-[10px] font-bold text-gray-500 uppercase">Previsto (R$)</label>
                                    <input type="number" step="0.01" min="0" name="valor_previsto" value="{{ $p->valor_previsto }}" required class="{{ $input }} font-bold"></div>
                                <div><label class="text-[10px] font-bold text-gray-500 uppercase">Gasto real (R$)</label>
                                    <input type="number" step="0.01" min="0" name="valor_gasto" value="{{ $p->valor_gasto }}" class="{{ $input }}" placeholder="ao concluir"></div>
                                <div class="md:col-span-3"><label class="text-[10px] font-bold text-gray-500 uppercase">Observações</label>
                                    <input type="text" name="observacao" value="{{ $p->observacao }}" maxlength="500" class="{{ $input }}"></div>
                                <div class="md:col-span-1">
                                    <button class="w-full bg-gray-800 hover:bg-black text-white font-bold py-2 rounded text-sm">Salvar</button></div>
                            </form>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl border p-10 text-center text-gray-400 italic">
                            Nenhuma prioridade cadastrada. Clique em “+ Nova prioridade” para montar sua lista.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
