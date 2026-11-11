<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaldoInicial;

class ConfiguracaoController extends Controller
{
    // Se você já tiver um método que abre a tela de configurações em outro controller,
    // pode ignorar este método index() e usar apenas o salvarSaldoInicial.
    public function index()
    {
        return view('configuracoes.index'); // Altere para o caminho real da sua view
    }

   public function salvarSaldoInicial(Request $request)
    {
        $request->validate([
            'conta_instituicao' => 'required|string',
            'mes_referencia' => 'required|integer',
            'ano_referencia' => 'required|integer',
            'valor' => 'required|string',
        ]);

        // Limpa a formatação brasileira (R$ 48.979,76 -> 48979.76)
        $valorLimpo = str_replace(['R$', ' ', '.'], '', $request->valor);
        $valorLimpo = str_replace(',', '.', $valorLimpo);

        SaldoInicial::updateOrCreate(
            [
                'conta_instituicao' => strtolower($request->conta_instituicao),
                // O (int) garante que o mês 09 e 9 sejam tratados da mesma forma
                'mes_referencia' => (int) $request->mes_referencia,
                'ano_referencia' => (int) $request->ano_referencia,
            ],
            ['valor' => (float) $valorLimpo]
        );

        return back()->with('success', 'Saldo Inicial atualizado com sucesso!');
    }
}
