<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conciliacao;
use Illuminate\Support\Facades\DB;
use App\Services\LeitorArquivoService;

class ImportacaoController extends Controller
{
    public function index(Request $request)
    {
        $resumos = Conciliacao::select(
                'origem', 'conta_instituicao', 'mes_referencia', 'ano_referencia',
                DB::raw('COUNT(id) as total_linhas'),
                DB::raw('SUM(CASE WHEN tipo = "entrada" THEN valor ELSE 0 END) as total_entradas'),
                DB::raw('SUM(CASE WHEN tipo = "saida" THEN valor ELSE 0 END) as total_saidas')
            )
            ->groupBy('origem', 'conta_instituicao', 'mes_referencia', 'ano_referencia')
            ->orderBy('ano_referencia', 'desc')
            ->orderBy('mes_referencia', 'desc')
            ->get();

        $detalhes = null;
        $filtrosAtuais = null;

        if ($request->has('ver_detalhes')) {
            $detalhes = Conciliacao::where('origem', $request->origem)
                ->where('conta_instituicao', $request->conta)
                ->where('mes_referencia', $request->mes)
                ->where('ano_referencia', $request->ano)
                ->orderBy('data_transacao', 'desc')
                ->get();
            $filtrosAtuais = $request->all();
        }

        return view('importacao.index', compact('resumos', 'detalhes', 'filtrosAtuais'));
    }

    public function upload(Request $request, LeitorArquivoService $leitorService)
    {
        $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer',
            'tipo_arquivo' => 'required|string',
            'arquivo_importacao' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('arquivo_importacao')->getRealPath();
        $tipoArquivo = $request->input('tipo_arquivo');

        if ($tipoArquivo == 'banco') {
            $instituicao = $request->input('conta_banco');
        } elseif (str_contains($tipoArquivo, 'maquininha')) {
            $instituicao = $request->input('conta_maquininha');
        } else {
            $instituicao = 'vendas_sistema';
        }

        // 🛡️ TRAVA DE SEGURANÇA CONTRA DUPLICIDADE
        $jaExiste = Conciliacao::where('origem', $tipoArquivo)
            ->where('conta_instituicao', $instituicao)
            ->where('mes_referencia', $request->input('mes'))
            ->where('ano_referencia', $request->input('ano'))
            ->exists();

        if ($jaExiste) {
            return back()->withErrors([
                'arquivo_importacao' => "Já existe uma importação de " . strtoupper($instituicao) . " para o mês " . str_pad($request->input('mes'), 2, '0', STR_PAD_LEFT) . "/{$request->input('ano')}. Por favor, desça até a lista de arquivos importados e exclua o lote antigo antes de enviar um novo."
            ]);
        }

        // Se passou da trava, processa a leitura normalmente
        $registrosProcessados = $leitorService->processar(
            $path, $tipoArquivo, $instituicao, $request->input('mes'), $request->input('ano')
        );

        // O Gatilho da Inteligência: Se for venda de maquininha, chama o Auditor Automático!
        if ($tipoArquivo === 'maquininha_vendas') {
            $this->auditarTaxasLote($instituicao, $request->input('mes'), $request->input('ano'));
        }

        return redirect()->route('importacao.index', [
            'ver_detalhes' => 1,
            'origem' => $tipoArquivo,
            'conta' => $instituicao,
            'mes' => $request->input('mes'),
            'ano' => $request->input('ano')
        ])->with('status', "Sucesso! {$registrosProcessados} linhas importadas e auditadas.");
    }

    public function destroy(Request $request)
    {
        Conciliacao::where('origem', $request->origem)
            ->where('conta_instituicao', $request->conta)
            ->where('mes_referencia', $request->mes)
            ->where('ano_referencia', $request->ano)
            ->delete();

        return redirect()->route('importacao.index')->with('status', 'Arquivo e todos os seus registros foram excluídos com sucesso!');
    }

    // =========================================================================
    // 🕵️ DETETIVE DE AUDITORIA: Lê a descrição, cruza com a tabela e acha o erro
    // =========================================================================
    private function auditarTaxasLote($instituicao, $mes, $ano)
    {
        // Se no formulário veio 'itau', no banco salvamos a taxa como 'rede'
        $instBusca = ($instituicao === 'itau') ? 'rede' : $instituicao;

        $vendas = Conciliacao::where('origem', 'maquininha_vendas')
            ->where('conta_instituicao', $instituicao)
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->get();

        foreach ($vendas as $venda) {
            $desc = strtolower($venda->descricao);

            // 1. Adivinha a Bandeira pela descrição do extrato
            $bandeira = 'mastercard'; // Padrão
            if (str_contains($desc, 'visa') || str_contains($desc, 'electron')) $bandeira = 'visa';
            elseif (str_contains($desc, 'elo')) $bandeira = 'elo';
            elseif (str_contains($desc, 'amex') || str_contains($desc, 'american')) $bandeira = 'amex';
            elseif (str_contains($desc, 'hiper')) $bandeira = 'hipercard';
            elseif (str_contains($desc, 'diners')) $bandeira = 'diners';

            // 2. Adivinha a Modalidade
            $modalidade = 'credito';
            if (str_contains($desc, 'debito') || str_contains($desc, 'débito') || str_contains($desc, 'electron') || str_contains($desc, 'maestro')) {
                $modalidade = 'debito';
            }

            // 3. Adivinha as Parcelas (Procura por "02/06" -> Total = 6)
            $parcelas = 1;
            if (preg_match('/\/(\d{2})/', $desc, $matches)) {
                $parcelas = (int)$matches[1];
            } elseif (preg_match('/parc[^\d]*(\d+)/i', $desc, $matches)) {
                $parcelas = (int)$matches[1];
            }

            // 4. Vai buscar na nossa tabela de taxas configurada
            $taxaEsperada = DB::table('taxas_maquininhas')
                ->where('instituicao', $instBusca)
                ->where('bandeira', $bandeira)
                ->where('modalidade', $modalidade)
                ->where('parcelas', $parcelas)
                ->value('taxa');

            // 5. Preenche a transação com a auditoria
            if ($taxaEsperada !== null) {

                // Se a taxa cobrada não foi calculada na importação, calcula agora
                if (empty($venda->taxa_cobrada_percentual) && $venda->valor_bruto > 0) {
                    $venda->taxa_cobrada_percentual = (($venda->valor_bruto - $venda->valor) / $venda->valor_bruto) * 100;
                }

                $venda->taxa_acordada_percentual = (float)$taxaEsperada;

                // Divergência: Dá 0.05% de folga para erros de arredondamento de centavos
                $venda->divergencia_taxa = ($venda->taxa_cobrada_percentual > ((float)$taxaEsperada + 0.05));

                $venda->save();
            }
        }
    }
}
