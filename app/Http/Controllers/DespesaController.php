<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\PoteConfiguracao;
use App\Models\PoteDespesa;
use App\Models\PoteFaturamento;
use App\Models\PoteSaque;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PrioridadeInvestimento;

class DespesaController extends Controller
{
    /** Definição única dos cofres (evita repetir a mesma lógica 3x). */
    private const COFRES = [
        'reinvestimento' => ['coluna' => 'valor_reinvestimento', 'pct' => 'pct_reinvestimento', 'ciclo' => [3, 6, 9, 12]],
        'lucro'          => ['coluna' => 'valor_lucro',          'pct' => 'pct_lucro',          'ciclo' => [1, 7]],
        'investimento'   => ['coluna' => 'valor_investimento',   'pct' => 'pct_investimento',   'ciclo' => null],
    ];

    private function config(): PoteConfiguracao
    {
        $config = PoteConfiguracao::first();
        if (!$config) {
            $config = new PoteConfiguracao();
            $config->forceFill([
                'pct_operacao' => 60, 'pct_reinvestimento' => 15, 'pct_lucro' => 25, 'pct_investimento' => 0,
                'cota_socio1' => 40, 'cota_socio2' => 30, 'cota_socio3' => 30,
            ])->save();
        }
        return $config;
    }

    private function saldo(string $pote): float
    {
        $entrou = PoteFaturamento::sum(self::COFRES[$pote]['coluna']);
        $saiu   = PoteSaque::where('tipo_pote', $pote)->sum('valor');
        return round($entrou - $saiu, 2);
    }

    // ==========================================
    // TELA
    // ==========================================
    public function index(Request $request)
    {
        $mes = max(1, min(12, (int) $request->input('mes', now()->month)));
        $ano = (int) $request->input('ano', now()->year);
        $config = $this->config();

        $faturamentoMes = PoteFaturamento::where('mes', $mes)->where('ano', $ano)->first();
        $despesas       = PoteDespesa::where('mes', $mes)->where('ano', $ano)->orderBy('data_despesa', 'desc')->get();
        $totalDespesas  = $despesas->sum('valor');
        $totalFixas     = $despesas->where('recorrencia', 'fixa')->sum('valor');

        // Termômetro: fixas do mês atual x mês anterior
        $anterior    = Carbon::create($ano, $mes, 1)->subMonth();
        $fixasAnt    = PoteDespesa::where('mes', $anterior->month)->where('ano', $anterior->year)->where('recorrencia', 'fixa')->sum('valor');
        $variacaoFixas = $fixasAnt > 0 ? round((($totalFixas - $fixasAnt) / $fixasAnt) * 100, 1) : null;

        // Cofres
        $cofres = [];
        foreach (self::COFRES as $chave => $def) {
            $cofres[$chave] = [
                'pct'   => (float) $config->{$def['pct']},
                'saldo' => $this->saldo($chave),
                'ciclo' => $def['ciclo'],
                'aberto' => $def['ciclo'] ? in_array($mes, $def['ciclo']) : true,
            ];
        }

        // Rendimento estimado do pote de investimento (CDI)
        $taxaAnual = ($config->taxa_cdi / 100) * ($config->pct_do_cdi / 100);
        $rendMensal = pow(1 + $taxaAnual, 1 / 12) - 1;
        $rendimentoEstimado = round($cofres['investimento']['saldo'] * $rendMensal, 2);

        $socios = [
            ['nome' => $config->nome_socio1 ?: 'Sócio 1', 'cota' => (float) $config->cota_socio1],
            ['nome' => $config->nome_socio2 ?: 'Sócio 2', 'cota' => (float) $config->cota_socio2],
            ['nome' => $config->nome_socio3 ?: 'Sócio 3', 'cota' => (float) $config->cota_socio3],
        ];

        return view('despesas.index', [
            'mes' => $mes, 'ano' => $ano, 'config' => $config,
            'faturamentoMes' => $faturamentoMes,
            'despesas' => $despesas, 'totalDespesas' => $totalDespesas,
            'totalFixas' => $totalFixas, 'fixasAnt' => $fixasAnt, 'variacaoFixas' => $variacaoFixas,
            'saldoOperacao' => ($faturamentoMes->valor_operacao ?? 0) - $totalDespesas,
            'cofres' => $cofres, 'socios' => $socios,
            'rendimentoEstimado' => $rendimentoEstimado,
            'historicoFaturamentos' => PoteFaturamento::orderBy('ano', 'desc')->orderBy('mes', 'desc')->limit(24)->get(),
            'historicoSaques' => PoteSaque::orderBy('data_saque', 'desc')->limit(50)->get(),
            'despesasPessoais' => Despesa::where('tipo_despesa', 'pessoal')->get(),
        ]);
    }

    // ==========================================
    // CONFIGURAÇÕES
    // ==========================================
    public function salvarConfiguracaoPotes(Request $request)
    {
        $d = $request->validate([
            'pct_operacao' => 'required|numeric|min:0|max:100',
            'pct_reinvestimento' => 'required|numeric|min:0|max:100',
            'pct_lucro' => 'required|numeric|min:0|max:100',
            'pct_investimento' => 'required|numeric|min:0|max:100',
            'cota_socio1' => 'required|numeric|min:0|max:100',
            'cota_socio2' => 'required|numeric|min:0|max:100',
            'cota_socio3' => 'required|numeric|min:0|max:100',
            'reserva_tipo' => 'required|in:valor,percentual',
            'reserva_valor' => 'required|numeric|min:0',
            'taxa_cdi' => 'required|numeric|min:0|max:100',
            'pct_do_cdi' => 'required|numeric|min:0|max:500',
            'nome_socio1' => 'nullable|string|max:100',
            'nome_socio2' => 'nullable|string|max:100',
            'nome_socio3' => 'nullable|string|max:100',
        ]);

        if (round($d['pct_operacao'] + $d['pct_reinvestimento'] + $d['pct_lucro'] + $d['pct_investimento'], 2) != 100.00) {
            return back()->withInput()->withErrors(['erro_potes' => 'A soma dos potes (Operação + Reinvestimento + Lucro + Investimento) deve ser exatamente 100%.']);
        }
        if (round($d['cota_socio1'] + $d['cota_socio2'] + $d['cota_socio3'], 2) != 100.00) {
            return back()->withInput()->withErrors(['erro_cotas' => 'A soma das cotas societárias deve ser exatamente 100%.']);
        }
        if ($d['reserva_tipo'] === 'percentual' && $d['reserva_valor'] > 100) {
            return back()->withInput()->withErrors(['erro_reserva' => 'A reserva em percentual não pode passar de 100%.']);
        }

        $this->config()->forceFill($d)->save();
        return back()->with('success', 'Configurações atualizadas com sucesso!');
    }

    // ==========================================
    // FATURAMENTO -> SPLIT
    // ==========================================
    public function salvarFaturamentoPotes(Request $request)
    {
        $d = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'faturamento_informado' => 'required|numeric|min:0',
        ]);

        $c   = $this->config();
        $fat = (float) $d['faturamento_informado'];

        // 1) Reserva de investimento livre sai ANTES da distribuição
        $reserva = $c->reserva_tipo === 'percentual' ? $fat * ($c->reserva_valor / 100) : (float) $c->reserva_valor;
        $reserva = min($reserva, $fat);
        $base    = $fat - $reserva;

        // 2) Split dos potes sobre o restante
        $reg = PoteFaturamento::where('mes', $d['mes'])->where('ano', $d['ano'])->first() ?? new PoteFaturamento();
        $reg->forceFill([
            'mes' => $d['mes'], 'ano' => $d['ano'],
            'faturamento_informado' => $fat,
            'valor_reserva'         => round($reserva, 2),
            'valor_operacao'        => round($base * $c->pct_operacao / 100, 2),
            'valor_reinvestimento'  => round($base * $c->pct_reinvestimento / 100, 2),
            'valor_lucro'           => round($base * $c->pct_lucro / 100, 2),
            'valor_investimento'    => round($base * $c->pct_investimento / 100 + $reserva, 2), // reserva vai para o cofre de investimento
        ])->save();

        return redirect()->route('despesas.index', ['mes' => $d['mes'], 'ano' => $d['ano']])
            ->with('success', 'Faturamento distribuído pelos potes com sucesso!');
    }

    // ==========================================
    // DESPESAS DO MÊS (únicas, fixas e parceladas)
    // ==========================================
    public function salvarDespesaPotes(Request $request)
    {
        $d = $request->validate([
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'data_despesa' => 'required|date',
            'recorrencia' => 'required|in:unica,fixa,parcelada',
            'total_parcelas' => 'nullable|integer|min:2|max:60',
        ]);

        $data  = Carbon::parse($d['data_despesa']);
        $total = $d['recorrencia'] === 'parcelada' ? (int) ($d['total_parcelas'] ?? 0) : 1;
        if ($d['recorrencia'] === 'parcelada' && $total < 2) {
            return back()->withInput()->withErrors(['erro_despesa' => 'Informe o número de parcelas (mínimo 2).']);
        }

        // Parceladas geram uma linha por mês, já nos meses futuros
        for ($i = 0; $i < $total; $i++) {
            $dt = $data->copy()->addMonthsNoOverflow($i);
            (new PoteDespesa())->forceFill([
                'mes' => $dt->month, 'ano' => $dt->year,
                'descricao' => $total > 1 ? $d['descricao'] . ' (' . ($i + 1) . '/' . $total . ')' : $d['descricao'],
                'valor' => $d['valor'],
                'data_despesa' => $dt->toDateString(),
                'recorrencia' => $d['recorrencia'],
                'parcela_atual' => $total > 1 ? $i + 1 : null,
                'total_parcelas' => $total > 1 ? $total : null,
            ])->save();
        }

        return back()->with('success', $total > 1 ? "Despesa parcelada lançada em {$total} meses." : 'Despesa guardada com sucesso!');
    }

    /** Traz as despesas fixas do mês anterior para o mês selecionado (sem duplicar). */
    public function copiarFixas(Request $request)
    {
        $d = $request->validate(['mes' => 'required|integer|min:1|max:12', 'ano' => 'required|integer']);
        $alvo = Carbon::create($d['ano'], $d['mes'], 1);
        $ant  = $alvo->copy()->subMonth();

        $existentes = PoteDespesa::where('mes', $alvo->month)->where('ano', $alvo->year)->where('recorrencia', 'fixa')->pluck('descricao')->all();
        $copiadas = 0;

        foreach (PoteDespesa::where('mes', $ant->month)->where('ano', $ant->year)->where('recorrencia', 'fixa')->get() as $f) {
            if (in_array($f->descricao, $existentes)) continue;
            $dia = min(Carbon::parse($f->data_despesa)->day, $alvo->daysInMonth);
            (new PoteDespesa())->forceFill([
                'mes' => $alvo->month, 'ano' => $alvo->year, 'descricao' => $f->descricao, 'valor' => $f->valor,
                'data_despesa' => $alvo->copy()->day($dia)->toDateString(), 'recorrencia' => 'fixa',
            ])->save();
            $copiadas++;
        }

        return back()->with('success', $copiadas ? "{$copiadas} despesa(s) fixa(s) copiada(s) do mês anterior." : 'Nenhuma fixa nova para copiar.');
    }

    public function deletarDespesaPotes($id)
    {
        PoteDespesa::destroy($id);
        return back()->with('success', 'Despesa removida.');
    }

    // ==========================================
    // SAQUES / USO DOS COFRES
    // ==========================================
    public function registrarSaquePotes(Request $request)
    {
        $d = $request->validate([
            'tipo_pote' => 'required|in:reinvestimento,lucro,investimento',
            'valor' => 'required|numeric|min:0.01',
            'descricao_beneficiario' => 'required_without:distribuir|nullable|string|max:255',
            'data_saque' => 'required|date',
            'distribuir' => 'nullable|boolean',
        ]);

        // Trava: não retira mais do que existe no cofre
        if ($d['valor'] > $this->saldo($d['tipo_pote'])) {
            return back()->withInput()->withErrors(['erro_saque' => 'O valor é maior que o saldo disponível no cofre.']);
        }

        if ($d['tipo_pote'] === 'lucro' && $request->boolean('distribuir')) {
            // Distribuição automática pelas cotas, 1 registro por sócio
            $c = $this->config();
            $partes = [[$c->nome_socio1 ?: 'Sócio 1', $c->cota_socio1], [$c->nome_socio2 ?: 'Sócio 2', $c->cota_socio2], [$c->nome_socio3 ?: 'Sócio 3', $c->cota_socio3]];
            $restante = round($d['valor'], 2);
            foreach ($partes as $i => [$nome, $cota]) {
                $v = $i === 2 ? $restante : round($d['valor'] * $cota / 100, 2);
                $restante = round($restante - $v, 2);
                (new PoteSaque())->forceFill([
                    'tipo_pote' => 'lucro', 'valor' => $v, 'descricao_beneficiario' => $nome, 'data_saque' => $d['data_saque'],
                ])->save();
            }
        } else {
            (new PoteSaque())->forceFill([
                'tipo_pote' => $d['tipo_pote'], 'valor' => $d['valor'],
                'descricao_beneficiario' => $d['descricao_beneficiario'], 'data_saque' => $d['data_saque'],
            ])->save();
        }

        return back()->with('success', 'Retirada registrada com sucesso!');
    }

    public function gerarRelatorioPdf(Request $request)
    {
        return back()->with('success', 'Gerador de PDF será ligado na próxima fase.');
    }

    // ==========================================
    // PRIORIDADES DE INVESTIMENTO
    // ==========================================
    public function salvarPrioridade(Request $request)
    {
        $d = $request->validate([
            'titulo' => 'required|string|max:150',
            'categoria' => 'nullable|string|max:60',
            'prioridade' => 'required|in:urgente,importante,pode_esperar',
            'valor_previsto' => 'required|numeric|min:0',
            'prazo' => 'nullable|date',
            'observacao' => 'nullable|string|max:500',
        ]);

        PrioridadeInvestimento::create($d);
        return back()->with('success', 'Prioridade adicionada ao painel!');
    }

    public function atualizarPrioridade(Request $request, $id)
    {
        $p = PrioridadeInvestimento::findOrFail($id);

        $d = $request->validate([
            'titulo' => 'nullable|string|max:150',
            'categoria' => 'nullable|string|max:60',
            'prioridade' => 'nullable|in:urgente,importante,pode_esperar',
            'status' => 'nullable|in:pendente,em_andamento,concluido',
            'valor_previsto' => 'nullable|numeric|min:0',
            'valor_gasto' => 'nullable|numeric|min:0',
            'prazo' => 'nullable|date',
            'observacao' => 'nullable|string|max:500',
        ]);

        // Se marcou como concluído agora, grava a data
        if (isset($d['status']) && $d['status'] === 'concluido' && $p->status !== 'concluido') {
            $d['concluido_em'] = now();
        }

        $p->update($d);
        return back()->with('success', 'Prioridade atualizada com sucesso!');
    }

    public function deletarPrioridade($id)
    {
        PrioridadeInvestimento::destroy($id);
        return back()->with('success', 'Prioridade excluída.');
    }
}
