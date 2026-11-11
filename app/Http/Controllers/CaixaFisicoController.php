<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CaixaFisico;

class CaixaFisicoController extends Controller
{
    public function index(Request $request)
    {
        // Define o mês e ano atuais como padrão, ou capta os selecionados no filtro
        $mes = $request->input('mes', date('m'));
        $ano = $request->input('ano', date('Y'));

        // Prepara a consulta à base de dados filtrando pelas datas
        $query = CaixaFisico::whereMonth('data_movimentacao', $mes)
                            ->whereYear('data_movimentacao', $ano);

        // Calcula o Saldo (Entradas - Saídas) do período selecionado
        $entradas = (clone $query)->where('tipo', 'entrada')->sum('valor');
        $saidas = (clone $query)->where('tipo', 'saida')->sum('valor');
        $saldoAtual = $entradas - $saidas;

        // Obtém o histórico filtrado
        $movimentacoes = (clone $query)->orderBy('data_movimentacao', 'desc')->orderBy('id', 'desc')->get();

        return view('caixa', compact('saldoAtual', 'movimentacoes', 'mes', 'ano'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descricao' => 'required|string|max:255',
            'tipo' => 'required|in:entrada,saida',
            'valor' => 'required|numeric|min:0.01',
            'data_movimentacao' => 'required|date',
        ]);

        CaixaFisico::create($request->all());

        return redirect()->route('caixa.index')->with('status', 'Registo adicionado com sucesso!');
    }
}
