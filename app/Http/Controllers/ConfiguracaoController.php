<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\SaldoInicial;

class ConfiguracaoController extends Controller
{
    public const CONTAS = ['bradesco' => 'Bradesco', 'itau' => 'Itaú'];

    public function index()
    {
        $saldos = SaldoInicial::orderByDesc('ano_referencia')
            ->orderByDesc('mes_referencia')
            ->orderBy('conta_instituicao')
            ->get();

        return view('configuracoes.index', ['saldos' => $saldos, 'contas' => self::CONTAS]);
    }

    public function salvarSaldoInicial(Request $request)
    {
        $d = $this->validar($request);

        SaldoInicial::updateOrCreate(
            ['conta_instituicao' => $d['conta_instituicao'], 'mes_referencia' => $d['mes_referencia'], 'ano_referencia' => $d['ano_referencia']],
            ['valor' => $d['valor']]
        );

        return back()->with('success', 'Saldo Inicial atualizado com sucesso!');
    }

    public function atualizarSaldoInicial(Request $request, SaldoInicial $saldo)
    {
        $d = $this->validar($request, $saldo);
        $saldo->update($d);

        return back()->with('success', 'Saldo Inicial alterado com sucesso!');
    }

    public function excluirSaldoInicial(SaldoInicial $saldo)
    {
        $saldo->delete();

        return back()->with('success', 'Saldo Inicial excluído.');
    }

    /** Valida e normaliza (conta minúscula, mês/ano inteiros, "R$ 48.979,76" -> 48979.76). */
    private function validar(Request $request, ?SaldoInicial $ignorar = null): array
    {
        $request->merge(['conta_instituicao' => strtolower((string) $request->conta_instituicao)]);

        $d = $request->validate([
            'conta_instituicao' => ['required', Rule::in(array_keys(self::CONTAS))],
            'mes_referencia' => 'required|integer|min:1|max:12',
            'ano_referencia' => 'required|integer|min:2000|max:2100',
            'valor' => ['required', 'string', 'regex:/^\s*(R\$)?\s*-?[\d.]*\d(,\d{1,2})?\s*$/'],
        ], ['valor.regex' => 'Informe o valor no formato 48.979,76.']);

        $d['mes_referencia'] = (int) $d['mes_referencia'];
        $d['ano_referencia'] = (int) $d['ano_referencia'];
        $d['valor'] = (float) str_replace(',', '.', str_replace(['R$', ' ', '.'], '', $d['valor']));

        if ($ignorar) {
            $duplicado = SaldoInicial::where('conta_instituicao', $d['conta_instituicao'])
                ->where('mes_referencia', $d['mes_referencia'])
                ->where('ano_referencia', $d['ano_referencia'])
                ->whereKeyNot($ignorar->id)
                ->exists();
            if ($duplicado) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'mes_referencia' => 'Já existe saldo para essa conta neste mês/ano.',
                ]);
            }
        }

        return $d;
    }
}
