<?php

namespace App\Http\Controllers;

use App\Models\Conciliacao;
use App\Models\SaldoInicial;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoje = Carbon::now();
        $mesAtual = $hoje->month;
        $anoAtual = $hoje->year;

        // 1. Movimento bancário do mês (uma consulta para entradas e saídas)
        $banco = Conciliacao::where('origem', 'banco')
            ->where('mes_referencia', $mesAtual)
            ->where('ano_referencia', $anoAtual)
            ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) as entradas")
            ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) as saidas")
            ->first();

        $receitasMes = (float) $banco->entradas;
        $despesasMes = (float) $banco->saidas;

        // 2. Saldo atual = saldos iniciais do mês + entradas − saídas
        $saldosIniciais = SaldoInicial::where('mes_referencia', $mesAtual)
            ->where('ano_referencia', $anoAtual)
            ->get(['valor']);
        $temSaldoInicial = $saldosIniciais->isNotEmpty();
        $saldoAtual = (float) $saldosIniciais->sum('valor') + $receitasMes - $despesasMes;

        // 3. Maquininha a receber no ano = vendas − recebidos (valores brutos, como no DRE)
        $maquininha = Conciliacao::where('ano_referencia', $anoAtual)
            ->whereIn('origem', ['maquininha_vendas', 'maquininha_recebidos'])
            ->selectRaw("COALESCE(SUM(CASE WHEN origem = 'maquininha_vendas' THEN COALESCE(valor_bruto, valor) ELSE 0 END), 0) as vendas")
            ->selectRaw("COALESCE(SUM(CASE WHEN origem = 'maquininha_recebidos' THEN COALESCE(valor_bruto, valor) ELSE 0 END), 0) as recebidos")
            ->first();
        $aReceberMaquininha = max(0.0, (float) $maquininha->vendas - (float) $maquininha->recebidos);

        // 4. Últimas 6 movimentações bancárias
        $ultimasTransacoes = Conciliacao::where('origem', 'banco')
            ->orderByDesc('data_transacao')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'data_transacao', 'descricao', 'tipo', 'valor', 'categoria', 'conta_instituicao']);

        $resultadoMes = $receitasMes - $despesasMes;

        return view('dashboard', compact(
            'saldoAtual', 'receitasMes', 'despesasMes', 'aReceberMaquininha', 'ultimasTransacoes',
            'resultadoMes', 'temSaldoInicial', 'mesAtual', 'anoAtual'
        ));
    }
}
