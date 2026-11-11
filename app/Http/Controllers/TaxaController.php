<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaxaMaquininha;

class TaxaController extends Controller
{
    public function index()
    {
        $taxas = TaxaMaquininha::orderBy('instituicao')->orderBy('modalidade')->get();
        // Mudou o nome da view aqui:
        return view('configuracoes.taxas', compact('taxas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'instituicao' => 'required|string',
            'bandeira' => 'nullable|string',
            'modalidade' => 'required|string',
            'parcela_de' => 'required|integer|min:1',
            'parcela_ate' => 'required|integer|min:1',
            'taxa_percentual' => 'required|numeric|min:0'
        ]);

        $dados = $request->all();
        $dados['taxa_percentual'] = str_replace(',', '.', $dados['taxa_percentual']);

        TaxaMaquininha::create($dados);

        // Mudou o redirecionamento
        return redirect()->route('configuracoes.taxas')->with('status', 'Taxa cadastrada com sucesso!');
    }

    public function destroy(TaxaMaquininha $taxa)
    {
        $taxa->delete();
        return redirect()->route('configuracoes.taxas')->with('status', 'Taxa removida com sucesso!');
    }
}
