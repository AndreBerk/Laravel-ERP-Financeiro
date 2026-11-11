@php
    // 🔄 CÁLCULO INTELIGENTE DO SALDO INICIAL (COM TRANSPORTE CONTÍNUO)
    $saldoInicial = 0;
    $mesAtual = (int)$filtrosAtuais['mes'];
    $anoAtual = (int)$filtrosAtuais['ano'];
    $contaAtual = strtolower($filtrosAtuais['conta']);

    // O sistema viaja desde Janeiro até ao mês atual para transportar o saldo
    for ($m = 1; $m <= $mesAtual; $m++) {
        $saldoManual = \App\Models\SaldoInicial::where('conta_instituicao', $contaAtual)
            ->where('mes_referencia', $m)
            ->where('ano_referencia', $anoAtual)
            ->value('valor');

        // Se houver um saldo digitado nas configurações para este mês, atualiza a base
        if ($saldoManual !== null) {
            $saldoInicial = (float) $saldoManual;
        }

        // Se ainda não chegámos ao mês que estamos a visualizar, soma as movimentações
        // para transportar o saldo final deste mês ($m) como saldo inicial do próximo.
        if ($m < $mesAtual) {
            $entradasPassadas = \App\Models\Conciliacao::where('origem', 'banco')
                ->where('tipo', 'entrada')
                ->where('conta_instituicao', $contaAtual)
                ->where('mes_referencia', $m)
                ->where('ano_referencia', $anoAtual)
                ->sum('valor');

            $saidasPassadas = \App\Models\Conciliacao::where('origem', 'banco')
                ->where('tipo', 'saida')
                ->where('conta_instituicao', $contaAtual)
                ->where('mes_referencia', $m)
                ->where('ano_referencia', $anoAtual)
                ->sum('valor');

            $saldoInicial = $saldoInicial + $entradasPassadas - $saidasPassadas;
        }
    }

    $totalEntradas = $detalhes->where('tipo', 'entrada')->sum('valor');
    $totalSaidas = $detalhes->where('tipo', 'saida')->sum('valor');

    // O cálculo real e final do dinheiro na conta neste mês!
    $saldoAtual = $saldoInicial + $totalEntradas - $totalSaidas;

    // Identificadores de Origem
    $isVendaSistema = $filtrosAtuais['origem'] === 'sistema_vendas';
    $isMaquininha = $filtrosAtuais['origem'] === 'maquininha_vendas';
    $isRecebimento = $filtrosAtuais['origem'] === 'maquininha_recebidos';
    $isBanco = $filtrosAtuais['origem'] === 'banco';

    // Variáveis para Auditoria de Taxas
    $totalBruto = $detalhes->sum('valor_bruto');
    $totalDivergencias = $detalhes->where('divergencia_taxa', true)->count();

    // 📡 RADAR DE CONCILIAÇÃO BANCÁRIA
    $extratosBancarios = \App\Models\Conciliacao::where('origem', 'banco')
        ->where('mes_referencia', $mesAtual)
        ->where('ano_referencia', $anoAtual)
        ->get();
@endphp

<!-- Cabeçalho Customizado de Detalhes -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="font-semibold text-2xl text-gray-800">
            🔍 Auditando: <span class="text-blue-600">{{ strtoupper($filtrosAtuais['conta']) }}</span>
        </h2>
        <p class="text-gray-500">Referência: {{ str_pad($filtrosAtuais['mes'], 2, '0', STR_PAD_LEFT) }}/{{ $filtrosAtuais['ano'] }} | Origem: {{ strtoupper(str_replace('_', ' ', $filtrosAtuais['origem'])) }}</p>
    </div>
    <a href="{{ route('importacao.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded shadow transition">
        ⬅️ Voltar ao Painel
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
    <p class="text-sm text-gray-500 font-bold mb-4">Total de registos (Lotes): {{ $detalhes->count() }}</p>

    <!-- Cartões de Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 border-b border-gray-100 pb-6 mb-6">
        @if($isMaquininha || $isRecebimento)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 shadow-sm">
                <p class="text-xs font-bold text-gray-600 uppercase mb-1">Valor Total Bruto (Antes das Taxas)</p>
                <p class="text-xl font-bold text-gray-800">R$ {{ number_format($totalBruto, 2, ',', '.') }}</p>
            </div>

            @if($isRecebimento)
                <div class="bg-red-50 rounded-lg p-4 border border-red-100 shadow-sm">
                    <p class="text-xs font-bold text-red-600 uppercase mb-1">Taxas Abatidas / Descontos (-)</p>
                    <p class="text-xl font-bold text-red-700">R$ {{ number_format($totalBruto - $totalEntradas, 2, ',', '.') }}</p>
                </div>
            @endif

            <div class="bg-green-50 rounded-lg p-4 border border-green-100 shadow-sm">
                <p class="text-xs font-bold text-green-600 uppercase mb-1">{{ $isRecebimento ? 'Valor Líquido a Receber (=)' : 'Valor Total Líquido (=)' }}</p>
                <p class="text-xl font-bold text-green-700">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</p>
            </div>

            @if($isMaquininha)
                <div class="{{ $totalDivergencias > 0 ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-100' }} rounded-lg p-4 border shadow-sm">
                    <p class="text-xs font-bold {{ $totalDivergencias > 0 ? 'text-red-600' : 'text-blue-600' }} uppercase mb-1">Status de Auditoria (Taxas)</p>
                    <p class="text-lg font-bold {{ $totalDivergencias > 0 ? 'text-red-700' : 'text-blue-700' }}">
                        {{ $totalDivergencias > 0 ? "⚠ {$totalDivergencias} divergências!" : '✅ Todas as taxas corretas' }}
                    </p>
                </div>
            @endif
        @else
            <!-- CARTÕES DO BANCO (Com Saldo Inteligente) -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 shadow-sm">
                <p class="text-sm font-medium text-gray-600 mb-1">Saldo Anterior (Transportado)</p>
                <p class="text-xl font-bold text-gray-800">R$ {{ number_format($saldoInicial, 2, ',', '.') }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border border-green-100 shadow-sm">
                <p class="text-sm font-medium text-green-600 mb-1">Total de Entradas (+)</p>
                <p class="text-xl font-bold text-green-700">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</p>
            </div>
            @if(!$isVendaSistema)
                <div class="bg-red-50 rounded-lg p-4 border border-red-100 shadow-sm">
                    <p class="text-sm font-medium text-red-600 mb-1">Total de Saídas (-)</p>
                    <p class="text-xl font-bold text-red-700">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</p>
                </div>
                <div class="{{ $saldoAtual >= 0 ? 'bg-blue-50 border-blue-200' : 'bg-yellow-50 border-yellow-200' }} rounded-lg p-4 border shadow-sm">
                    <p class="text-sm font-medium {{ $saldoAtual >= 0 ? 'text-blue-700' : 'text-yellow-700' }} mb-1">Saldo Atual (=)</p>
                    <p class="text-2xl font-bold {{ $saldoAtual >= 0 ? 'text-blue-800' : 'text-yellow-800' }}">R$ {{ number_format($saldoAtual, 2, ',', '.') }}</p>
                </div>
            @else
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-100 shadow-sm md:col-span-2">
                    <p class="text-sm font-medium text-purple-600 mb-1">Aviso do Sistema</p>
                    <p class="text-md font-bold text-purple-700">Os valores acima servem apenas como comparativo bruto para o DRE.</p>
                </div>
            @endif
        @endif
    </div>

    <!-- Tabela de Movimentações -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th class="px-6 py-3">Data</th>
                    <th class="px-6 py-3">Descrição / Lote</th>

                    @if($isMaquininha)
                        <th class="px-6 py-3 text-right">Valor Bruto</th>
                        <th class="px-6 py-3 text-center border-l border-gray-200">Taxa Banco</th>
                        <th class="px-6 py-3 text-center">Taxa Sistema</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    @endif

                    @if($isRecebimento)
                        <th class="px-6 py-3 text-right border-l border-gray-200">Total Bruto Original</th>
                        <th class="px-6 py-3 text-center text-red-600">Taxas Abatidas (-)</th>
                        <th class="px-6 py-3 text-center border-l border-gray-200">Auditoria Bancária (Extrato)</th>
                    @endif

                    @if(!$isMaquininha && !$isRecebimento)
                        <th class="px-6 py-3 text-center">Tipo</th>
                    @endif

                    <th class="px-6 py-3 text-right">Valor Líquido Depositado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($detalhes as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($transaction->data_transacao)->format('d/m/Y') }}</td>

                        <td class="px-6 py-4 font-medium text-gray-900">{{ $transaction->descricao }}</td>

                        @if($isMaquininha)
                            <!-- Colunas exclusivas da Auditoria de Taxas (Vendas) -->
                            <td class="px-6 py-4 text-right">
                                @if($transaction->valor_bruto > 0)
                                    R$ {{ number_format($transaction->valor_bruto, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center border-l border-gray-200 font-bold {{ $transaction->divergencia_taxa ? 'text-red-600' : 'text-gray-600' }}">
                                {{ $transaction->taxa_cobrada_percentual > 0 ? number_format($transaction->taxa_cobrada_percentual, 2, ',', '.') . '%' : '-' }}
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500">
                                {{ $transaction->taxa_acordada_percentual > 0 ? number_format($transaction->taxa_acordada_percentual, 2, ',', '.') . '%' : 'Não config.' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($transaction->divergencia_taxa)
                                    <span title="O banco cobrou diferente do acordado!" class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded cursor-help">Divergente</span>
                                @else
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">OK</span>
                                @endif
                            </td>
                        @endif

                        @if($isRecebimento)
                            <!-- Colunas Exclusivas de Recebimentos (Consolidados) -->

                            <!-- Bruto Original -->
                            <td class="px-6 py-4 text-right border-l border-gray-200 text-gray-600">
                                @if($transaction->valor_bruto > 0)
                                    R$ {{ number_format($transaction->valor_bruto, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>

                            <!-- Taxas Retidas pelo Caminho -->
                            <td class="px-6 py-4 text-center font-bold text-red-600">
                                @if($transaction->valor_bruto > $transaction->valor)
                                    - R$ {{ number_format($transaction->valor_bruto - $transaction->valor, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>

                            <!-- Radar do Extrato Bancário -->
                            @php
                                // O Radar procura no extrato bancário se houve uma entrada naquele dia com aquele valor EXATO
                                $bateuBanco = $extratosBancarios->where('data_transacao', $transaction->data_transacao)
                                                                ->where('valor', $transaction->valor)
                                                                ->first();
                            @endphp
                            <td class="px-6 py-4 text-center border-l border-gray-200">
                                @if($bateuBanco)
                                    <span title="Confirmado no {{ strtoupper($bateuBanco->conta_instituicao) }}" class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded cursor-help">✅ Confirmado no Banco</span>
                                @else
                                    <span title="Falta importar o extrato do banco ou o banco não depositou" class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded cursor-help">⏳ Pendente Extrato</span>
                                @endif
                            </td>
                        @endif

                        @if(!$isMaquininha && !$isRecebimento)
                            <td class="px-6 py-4 text-center">
                                @if($transaction->tipo === 'entrada')
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Entrada</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Saída</span>
                                @endif
                            </td>
                        @endif

                        <!-- Valor Final -->
                        <td class="px-6 py-4 text-right font-bold {{ $transaction->tipo === 'entrada' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->tipo === 'entrada' ? '+' : '-' }} R$ {{ number_format($transaction->valor, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        @php
                            $colunas = 5; // Extrato
                            if($isMaquininha) $colunas = 7;
                            if($isRecebimento) $colunas = 6;
                        @endphp
                        <td colspan="{{ $colunas }}" class="px-6 py-8 text-center text-gray-500">Nenhuma transação encontrada neste período.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
