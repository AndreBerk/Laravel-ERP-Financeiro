<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conciliacao;
use App\Models\SaldoInicial;
use Carbon\Carbon;

class DreController extends Controller
{
    /**
     * Categorias que NÃO são dinheiro novo (nem despesa real): movimentações entre
     * contas próprias, aplicações e resgates. Ficam fora do DRE consolidado, mas
     * continuam entrando nos fluxos de cada banco para o saldo bater com o extrato.
     * >>> Ajuste esta lista com os nomes exatos das categorias do seu sistema. <<<
     */
    private const CATEGORIAS_NAO_RECEITA = [
        'Transferência entre Contas',
        'Transferência Interna',
        'Transferência Própria',
        'Aplicação',
        'Aplicação Financeira',
        'Resgate',
        'Resgate Investimento',
    ];

    private function mesVazio(): array
    {
        return [
            'vendasBrutas' => 0.0, 'recebimentosBrutos' => 0.0, 'recebimentosLiquidos' => 0.0,
            'entradasBanco' => 0.0, 'saidasBanco' => 0.0, 'transferenciasInternas' => 0.0,
            'entradasBradesco' => 0.0, 'saidasBradesco' => 0.0,
            'entradasItau' => 0.0, 'saidasItau' => 0.0,
            'despesasPorCategoria' => [],
        ];
    }

    private function calcularDados(Request $request)
    {
        $mesInput = $request->input('mes', Carbon::now()->month);
        $ano = (int) $request->input('ano', Carbon::now()->year);
        $isAnual = $mesInput === 'todos';

        $naoReceita = array_map('mb_strtolower', self::CATEGORIAS_NAO_RECEITA);

        // ------------------------------------------------------------------
        // 1) UMA consulta agrupada para o ano todo (antes eram ~240 consultas)
        // ------------------------------------------------------------------
        $bruto = [];
        for ($m = 1; $m <= 12; $m++) {
            $bruto[$m] = $this->mesVazio();
        }

        $linhas = Conciliacao::where('ano_referencia', $ano)
            ->whereIn('origem', ['banco', 'maquininha_vendas', 'maquininha_recebidos'])
            ->selectRaw('mes_referencia as mes, origem, tipo, conta_instituicao as conta, categoria, SUM(valor) as total, SUM(valor_bruto) as bruto')
            ->groupBy('mes_referencia', 'origem', 'tipo', 'conta_instituicao', 'categoria')
            ->get();

        foreach ($linhas as $l) {
            $m = (int) $l->mes;
            if ($m < 1 || $m > 12) {
                continue;
            }
            $total = (float) $l->total;
            $valorBruto = (float) $l->bruto;
            $conta = mb_strtolower((string) $l->conta);
            $categoria = trim((string) $l->categoria);

            if ($l->origem === 'maquininha_vendas') {
                $bruto[$m]['vendasBrutas'] += $valorBruto;
            } elseif ($l->origem === 'maquininha_recebidos') {
                $bruto[$m]['recebimentosBrutos'] += $valorBruto;
                $bruto[$m]['recebimentosLiquidos'] += $total;
            } else { // banco
                $entrada = $l->tipo === 'entrada';
                $saida = $l->tipo === 'saida';

                // Fluxo por banco (inclui tudo, para o saldo bater com o extrato)
                if ($conta === 'bradesco') {
                    if ($entrada) $bruto[$m]['entradasBradesco'] += $total;
                    if ($saida) $bruto[$m]['saidasBradesco'] += $total;
                } elseif ($conta === 'itau') {
                    if ($entrada) $bruto[$m]['entradasItau'] += $total;
                    if ($saida) $bruto[$m]['saidasItau'] += $total;
                }

                // DRE consolidado (sem transferências internas / aplicações)
                if (in_array(mb_strtolower($categoria), $naoReceita, true)) {
                    $bruto[$m]['transferenciasInternas'] += $total;
                    continue;
                }
                if ($entrada) {
                    $bruto[$m]['entradasBanco'] += $total;
                }
                if ($saida) {
                    $bruto[$m]['saidasBanco'] += $total;
                    $nome = ($categoria === '' || $categoria === 'Movimentação Bancária') ? 'Outras Despesas' : $categoria;
                    $bruto[$m]['despesasPorCategoria'][$nome] = ($bruto[$m]['despesasPorCategoria'][$nome] ?? 0) + $total;
                }
            }
        }

        // Saldos digitados manualmente nas Configurações
        $manuais = [];
        foreach (SaldoInicial::where('ano_referencia', $ano)->whereIn('conta_instituicao', ['bradesco', 'itau'])->get() as $s) {
            $chave = $s->conta_instituicao . '-' . (int) $s->mes_referencia;
            if (!isset($manuais[$chave])) {
                $manuais[$chave] = (float) $s->valor;
            }
        }

        // ------------------------------------------------------------------
        // 2) Cálculo em cascata, de Janeiro a Dezembro
        // ------------------------------------------------------------------
        $somar = [
            'vendasBrutas', 'recebimentosBrutos', 'taxasMaquininha',
            'entradasBanco', 'receitaCaixa', 'saidasBanco', 'lucroLiquido',
            'maquininhaNoBanco', 'pixOutros',
            'entradasBradesco', 'saidasBradesco', 'entradasItau', 'saidasItau',
            'entradasTotalContas', 'saidasTotalContas',
        ];
        $totais = array_fill_keys($somar, 0.0);
        $mesesData = [];

        $saldoFinalBradescoAnterior = 0.0;
        $saldoFinalItauAnterior = 0.0;
        $vendasAcum = 0.0;
        $recebidoAcum = 0.0;
        $taxasAcum = 0.0;

        for ($m = 1; $m <= 12; $m++) {
            $d = $bruto[$m];

            // --- Maquininha ---
            $vendasBrutas = $d['vendasBrutas'];
            $recebimentosBrutos = $d['recebimentosBrutos'];
            $recebimentosLiquidos = $d['recebimentosLiquidos'];
            $taxasMaquininha = ($recebimentosBrutos > 0 && $recebimentosLiquidos > 0) ? $recebimentosBrutos - $recebimentosLiquidos : 0.0;

            // A receber = o que foi vendido no ano e ainda não caiu (crédito parcelado, débito D+1 etc.)
            $vendasAcum += $vendasBrutas;
            $recebidoAcum += $recebimentosBrutos;
            $taxasAcum += $taxasMaquininha;
            $aReceberBruto = max(0.0, $vendasAcum - $recebidoAcum);
            $taxaMedia = $recebidoAcum > 0 ? $taxasAcum / $recebidoAcum : 0.0;
            $aReceberLiquido = $aReceberBruto * (1 - $taxaMedia);
            $pctRecebido = $vendasAcum > 0 ? min(100, ($recebidoAcum / $vendasAcum) * 100) : 0.0;

            // --- DINHEIRO NOVO = entradas reais nos extratos do mês (PIX + depósitos da maquininha) ---
            $entradasBanco = $d['entradasBanco'];
            $receitaCaixa = $entradasBanco;
            $maquininhaNoBanco = min($recebimentosLiquidos, $entradasBanco);
            $pixOutros = max(0.0, $entradasBanco - $maquininhaNoBanco);

            $saidasBanco = $d['saidasBanco'];
            $lucroLiquido = $receitaCaixa - $saidasBanco;
            $margemLucro = $receitaCaixa > 0 ? ($lucroLiquido / $receitaCaixa) * 100 : 0.0;
            $previstoTotal = $receitaCaixa + $aReceberLiquido;

            arsort($d['despesasPorCategoria']);
            $categoriasDespesas = collect($d['despesasPorCategoria'])->map(function ($total, $nome) {
                return (object) ['categoria_nome' => $nome, 'total' => $total];
            })->values();

            // --- Saldos por banco (automático ou manual) ---
            $saldoInicialBradesco = $manuais['bradesco-' . $m] ?? $saldoFinalBradescoAnterior;
            $saldoInicialItau = $manuais['itau-' . $m] ?? $saldoFinalItauAnterior;

            $entradasBradesco = $d['entradasBradesco'];
            $saidasBradesco = $d['saidasBradesco'];
            $saldoFinalBradesco = $saldoInicialBradesco + $entradasBradesco - $saidasBradesco;

            $entradasItau = $d['entradasItau'];
            $saidasItau = $d['saidasItau'];
            $saldoFinalItau = $saldoInicialItau + $entradasItau - $saidasItau;

            $saldoFinalBradescoAnterior = $saldoFinalBradesco;
            $saldoFinalItauAnterior = $saldoFinalItau;

            // --- Saldo TOTAL (Bradesco + Itaú) ---
            $saldoInicialTotal = $saldoInicialBradesco + $saldoInicialItau;
            $entradasTotalContas = $entradasBradesco + $entradasItau;
            $saidasTotalContas = $saidasBradesco + $saidasItau;
            $saldoFinalTotal = $saldoFinalBradesco + $saldoFinalItau;

            $transferenciasInternas = $d['transferenciasInternas'];

            $mesesData[$m] = compact(
                'vendasBrutas', 'recebimentosBrutos', 'recebimentosLiquidos', 'taxasMaquininha',
                'aReceberBruto', 'aReceberLiquido', 'pctRecebido', 'vendasAcum', 'recebidoAcum',
                'entradasBanco', 'receitaCaixa', 'maquininhaNoBanco', 'pixOutros', 'previstoTotal',
                'saidasBanco', 'categoriasDespesas', 'lucroLiquido', 'margemLucro', 'transferenciasInternas',
                'saldoInicialBradesco', 'entradasBradesco', 'saidasBradesco', 'saldoFinalBradesco',
                'saldoInicialItau', 'entradasItau', 'saidasItau', 'saldoFinalItau',
                'saldoInicialTotal', 'entradasTotalContas', 'saidasTotalContas', 'saldoFinalTotal'
            );

            foreach ($somar as $chave) {
                $totais[$chave] += $mesesData[$m][$chave];
            }
        }

        $totais['margemLucro'] = $totais['receitaCaixa'] > 0 ? ($totais['lucroLiquido'] / $totais['receitaCaixa']) * 100 : 0;

        if ($isAnual) {
            return compact('isAnual', 'ano', 'mesInput', 'mesesData', 'totais');
        }

        $mes = (int) $mesInput;
        return array_merge(['isAnual' => false, 'ano' => $ano, 'mes' => $mes, 'mesInput' => $mesInput], $mesesData[$mes]);
    }

    public function index(Request $request)
    {
        return view('dre.index', $this->calcularDados($request));
    }

    public function imprimir(Request $request)
    {
        return view('dre.print', $this->calcularDados($request));
    }
}
