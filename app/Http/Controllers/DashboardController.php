<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conciliacao;
use App\Models\SaldoInicial;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $mesAtual = Carbon::now()->month;
        $anoAtual = Carbon::now()->year;

        // 1. DADOS BANCÁRIOS (O Dinheiro Real)
        $entradas = Conciliacao::where('origem', 'banco')
            ->where('tipo', 'entrada')
            ->where('mes_referencia', $mesAtual)
            ->where('ano_referencia', $anoAtual)
            ->sum('valor');

        $saidas = Conciliacao::where('origem', 'banco')
            ->where('tipo', 'saida')
            ->where('mes_referencia', $mesAtual)
            ->where('ano_referencia', $anoAtual)
            ->sum('valor');

        $saldosIniciais = SaldoInicial::where('mes_referencia', $mesAtual)
            ->where('ano_referencia', $anoAtual)
            ->sum('valor');

        $saldoBancarioFinal = $saldosIniciais + $entradas - $saidas;
        $lucroMes = $entradas - $saidas;

        // 2. LÓGICA DO SEMÁFORO INTELIGENTE
        if ($entradas == 0 && $saidas == 0) {
            // Sem movimento no mês
            $semaforo = [
                'cor_fundo' => 'bg-gray-500',
                'cor_icone' => 'bg-gray-400',
                'titulo' => 'Status: Aguardando Movimentação',
                'mensagem' => 'Ainda não há receitas ou despesas registradas para este mês.',
                'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
            ];
        } elseif ($lucroMes >= 0) {
            // Lucro (Verde)
            $semaforo = [
                'cor_fundo' => 'bg-green-500',
                'cor_icone' => 'bg-green-400',
                'titulo' => 'Status: Lucrativo',
                'mensagem' => 'As receitas estão superando as despesas. O caixa está saudável!',
                'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>'
            ];
        } else {
            // Prejuízo (Vermelho)
            $semaforo = [
                'cor_fundo' => 'bg-red-500',
                'cor_icone' => 'bg-red-400',
                'titulo' => 'Status: Alerta (Déficit)',
                'mensagem' => 'Atenção! As despesas deste mês estão maiores que as receitas.',
                'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path>'
            ];
        }

        // 3. ESPAÇOS RESERVADOS PARA OUTROS MÓDULOS
        // Para usar no futuro quando tiver a tabela de Caixa Físico e Despesas Fixas
        $caixaFisico = 0;

        // Verifica se a tabela/modelo Despesa existe para somar, senão manda 0
        $despesasFixas = 0;
        if (class_exists('App\Models\Despesa')) {
            $despesasFixas = \App\Models\Despesa::sum('valor');
        }

        return view('dashboard', compact(
            'saldoBancarioFinal', 'caixaFisico', 'despesasFixas',
            'semaforo', 'lucroMes', 'mesAtual', 'anoAtual'
        ));
    }
}
