<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight print:hidden">
            {{ __('Controle de Caixa - Dinheiro Físico') }}
        </h2>
    </x-slot>

    <!-- Na tela normal tem padding (py-12), na impressão removemos as margens (print:py-0) -->
    <div class="py-12 print:py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Cabeçalho Oficial do Relatório (Visível APENAS na impressão) -->
            <div class="hidden print:flex justify-between items-end border-b-2 border-black pb-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black uppercase text-black">Fechamento de Caixa</h1>
                    <h2 class="text-lg text-gray-800">Relatório de Movimentações (Físico)</h2>
                </div>
                <div class="text-right text-sm text-black font-medium">
                    <p>Período: <strong>{{ $mes }}/{{ $ano }}</strong></p>
                    <p>Impresso em: {{ date('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Alerta de Sucesso (Oculto na impressão) -->
            @if (session('status'))
                <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded shadow-sm print:hidden" role="alert">
                    <p class="font-bold">Sucesso!</p>
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            <!-- Filtros e Botão de Imprimir (Ocultos na impressão) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg print:hidden">
                <div class="p-6 text-gray-900 flex flex-col md:flex-row justify-between items-end gap-4">
                    <form method="GET" action="{{ route('caixa.index') }}" class="flex items-end gap-4">
                        @php
                            $meses = [
                                '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
                                '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
                                '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
                            ];
                        @endphp

                        <div>
                            <label for="mes" class="block text-sm font-medium text-gray-700">Mês</label>
                            <select name="mes" id="mes" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($meses as $num => $nome)
                                    <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="ano" class="block text-sm font-medium text-gray-700">Ano</label>
                            <select name="ano" id="ano" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @for($i = date('Y'); $i >= 2023; $i--)
                                    <option value="{{ $i }}" {{ $ano == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded shadow-sm transition-colors">
                            Filtrar
                        </button>
                    </form>

                    <button type="button" onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-sm transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Imprimir Relatório
                    </button>
                </div>
            </div>

            <!-- Resumo do Saldo -->
            <!-- Na impressão: borda preta sólida, sem cores de fundo, fonte maior -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 {{ $saldoAtual >= 0 ? 'border-emerald-500' : 'border-red-500' }} print:border-l-0 print:border-2 print:border-black print:shadow-none print:rounded-none mb-6">
                <div class="p-6 print:p-4 text-gray-900 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider print:text-black print:text-base">Saldo Final em Caixa</h3>
                        <p class="text-4xl font-extrabold text-gray-800 mt-1 print:text-black print:text-3xl">
                            R$ {{ number_format($saldoAtual, 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulário de Lançamento (Oculto na impressão) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg print:hidden mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4 text-gray-700 border-b pb-2">Novo Lançamento</h3>
                    <form method="POST" action="{{ route('caixa.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        @csrf
                        <div class="md:col-span-2">
                            <label for="descricao" class="block font-medium text-sm text-gray-700">Descrição</label>
                            <input id="descricao" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" type="text" name="descricao" required placeholder="Ex: Saldo Inicial, Venda Balcão" />
                        </div>
                        <div>
                            <label for="tipo" class="block font-medium text-sm text-gray-700">Tipo</label>
                            <select id="tipo" name="tipo" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full font-bold" required>
                                <option value="entrada" class="text-emerald-600">Entrada (+)</option>
                                <option value="saida" class="text-red-600">Saída (-)</option>
                            </select>
                        </div>
                        <div>
                            <label for="valor" class="block font-medium text-sm text-gray-700">Valor (R$)</label>
                            <input id="valor" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" type="number" step="0.01" min="0.01" name="valor" placeholder="0,00" required />
                        </div>
                        <div>
                            <label for="data_movimentacao" class="block font-medium text-sm text-gray-700">Data</label>
                            <input id="data_movimentacao" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" type="date" name="data_movimentacao" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="md:col-span-5 flex justify-end mt-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md shadow-sm transition-colors">
                                Adicionar Registro
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Histórico -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg print:shadow-none print:rounded-none">
                <div class="p-6 text-gray-900 print:p-0">
                    <h3 class="text-lg font-bold mb-4 text-gray-700 border-b pb-2 print:hidden">Histórico de Movimentações</h3>

                    <div class="overflow-x-auto print:overflow-visible">
                        <table class="min-w-full text-left text-sm whitespace-nowrap print:border-collapse print:border-2 print:border-black">
                            <thead class="uppercase tracking-wider border-b-2 border-gray-200 bg-gray-50 text-gray-500 font-bold print:bg-gray-200 print:text-black print:border-b-2 print:border-black">
                                <tr>
                                    <th scope="col" class="px-6 py-3 print:px-4 print:py-2 print:border-r print:border-black">Data</th>
                                    <th scope="col" class="px-6 py-3 print:px-4 print:py-2 print:border-r print:border-black">Descrição</th>
                                    <th scope="col" class="px-6 py-3 print:px-4 print:py-2 print:border-r print:border-black">Tipo</th>
                                    <th scope="col" class="px-6 py-3 print:px-4 print:py-2 text-right">Valor (R$)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movimentacoes as $mov)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors print:border-b print:border-black print:hover:bg-transparent">
                                        <td class="px-6 py-4 text-gray-500 print:text-black print:px-4 print:py-2 print:border-r print:border-black">
                                            {{ \Carbon\Carbon::parse($mov->data_movimentacao)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-700 print:text-black print:px-4 print:py-2 print:border-r print:border-black">
                                            {{ $mov->descricao }}
                                        </td>
                                        <td class="px-6 py-4 print:px-4 print:py-2 print:border-r print:border-black print:font-bold">
                                            @if($mov->tipo == 'entrada')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 print:bg-transparent print:text-black print:p-0">Entrada</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 print:bg-transparent print:text-black print:p-0">Saída</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-bold {{ $mov->tipo == 'entrada' ? 'text-emerald-600' : 'text-red-600' }} print:text-black print:px-4 print:py-2 text-right">
                                            {{ $mov->tipo == 'entrada' ? '+' : '-' }} {{ number_format($mov->valor, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 print:text-black print:p-4">
                                            Nenhum registro encontrado neste período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Área de Assinatura (Exclusivo para Impressão) -->
            <div class="hidden print:flex justify-end mt-24">
                <div class="w-1/2 text-center">
                    <div class="border-t-2 border-black pt-2">
                        <p class="font-bold text-black uppercase tracking-wider text-sm">Assinatura do Responsável</p>
                        <p class="text-xs text-black mt-1">Conferido e validado na data atual</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
