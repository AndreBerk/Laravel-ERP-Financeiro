@php
    $nomesMeses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $fmt = fn($v) => number_format((float) $v, 2, ',', '.');

    $caixaFisico = (float) $caixaFisico;
    $saldoBancarioFinal = (float) $saldoBancarioFinal;
    $despesasFixas = (float) $despesasFixas;
    $lucroMes = (float) $lucroMes;

    $totalDisponivel = $caixaFisico + $saldoBancarioFinal;
    // Quantos meses de despesas fixas o dinheiro disponível cobre
    $cobertura = $despesasFixas > 0 ? $totalDisponivel / $despesasFixas : null;

    $atalhos = [
        ['DRE', 'Resultado, saldos e previsão da maquininha', route('dre.index')],
        ['Importar extratos', 'Enviar PDF/OFX dos bancos e maquininhas', route('importacao.index')],
        ['Despesas', 'Despesas fixas e gestão por potes', route('despesas.index')],
        ['Caixa físico', 'Lançar entradas e saídas em dinheiro', route('caixa.index')],
        ['Taxas das maquininhas', 'Conferir taxas de débito e crédito', route('taxas.index')],
        ['Configurações', 'Saldos iniciais e ajustes', route('configuracoes.index')],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel de Resultados</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $nomesMeses[(int) $mesAtual] }} de {{ $anoAtual }}</p>
            </div>
            <p class="text-xs text-gray-400">Atualizado em {{ now()->format('d/m/Y \à\s H:i') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Termômetro de saúde financeira -->
            <div class="{{ $semaforo['cor_fundo'] }} rounded-xl shadow-lg p-6 sm:p-8 text-white flex items-center justify-between gap-6 transition-colors duration-500">
                <div class="min-w-0">
                    <h3 class="text-2xl font-bold tracking-tight">{{ $semaforo['titulo'] }}</h3>
                    <p class="mt-1.5 font-medium opacity-90">{{ $semaforo['mensagem'] }}</p>

                    @if($lucroMes != 0)
                        <p class="mt-4 text-sm font-bold bg-white bg-opacity-20 inline-block px-3 py-1.5 rounded-lg">
                            Resultado no mês: R$ {{ $fmt($lucroMes) }}
                        </p>
                    @endif
                </div>
                <div class="{{ $semaforo['cor_icone'] }} p-4 rounded-full bg-opacity-50 shrink-0">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $semaforo['icone'] !!}
                    </svg>
                </div>
            </div>

            <!-- Posição de caixa -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

                <div class="bg-gray-900 rounded-xl p-6 shadow-sm flex flex-col justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total disponível</p>
                    <p class="text-3xl font-extrabold text-white mt-3">R$ {{ $fmt($totalDisponivel) }}</p>
                    <p class="text-sm text-gray-400 mt-3">Bancos + caixa físico</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-blue-500 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Saldos bancários</p>
                    <p class="text-3xl font-extrabold text-gray-800 mt-3">R$ {{ $fmt($saldoBancarioFinal) }}</p>
                    <p class="text-sm text-gray-400 mt-3">Bradesco + Itaú (consolidado)</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-emerald-500 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Caixa dinheiro físico</p>
                    <p class="text-3xl font-extrabold text-gray-800 mt-3">R$ {{ $fmt($caixaFisico) }}</p>
                    <a href="{{ route('caixa.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium mt-3">Abrir caixa &rarr;</a>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-red-500 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Despesas fixas cadastradas</p>
                    <p class="text-3xl font-extrabold text-gray-800 mt-3">R$ {{ $fmt($despesasFixas) }}</p>
                    <p class="text-sm text-gray-400 mt-3">
                        @if($cobertura !== null)
                            Disponível cobre <strong class="text-gray-600">{{ number_format($cobertura, 1, ',', '.') }}</strong> {{ $cobertura >= 1.05 || $cobertura < 0.95 ? 'meses' : 'mês' }} de custo fixo
                        @else
                            Custo operacional fixo do mês
                        @endif
                    </p>
                </div>
            </div>

            <!-- Atalhos -->
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-3">Acesso rápido</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($atalhos as [$titulo, $descricao, $url])
                        <a href="{{ $url }}" class="group bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center justify-between hover:border-blue-200 hover:shadow-md transition">
                            <div>
                                <p class="font-bold text-gray-800 group-hover:text-blue-700 transition">{{ $titulo }}</p>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $descricao }}</p>
                            </div>
                            <span class="text-gray-300 group-hover:text-blue-500 text-xl transition">&rarr;</span>
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
