<?php

namespace App\Services;

use App\Models\Conciliacao;
use Carbon\Carbon;

class LeitorArquivoService
{
    public function processar($caminhoArquivo, $origem, $instituicao, $mes, $ano)
    {
        // 1. Detectar e forçar encoding para evitar problemas com acentuação
        $conteudo = file_get_contents($caminhoArquivo);
        $encoding = mb_detect_encoding($conteudo, ['UTF-8', 'ISO-8859-1', 'Windows-1252']);
        if ($encoding !== 'UTF-8') {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', $encoding);
        }

        $linhas = explode("\n", $conteudo);
        $registrosProcessados = 0;

        $iniciouLeitura = false;
        $delimitador = ',';
        $tipoArquivoCielo = ''; // Para diferenciar Venda de Recebimento na Cielo

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if (empty($linha)) continue;

            // ==========================================
            // DETECÇÃO INTELIGENTE DE CABEÇALHO
            // ==========================================

            // 1. Banco Itaú Padrão
            if (str_contains($linha, 'Data,Lançamento') || str_contains($linha, 'Data;Lançamento')) {
                $iniciouLeitura = true;
                $delimitador = str_contains($linha, ';') ? ';' : ',';
                continue;
            }
            // 2. Extrato REDE (Itaú Laranjinha)
            if (str_contains(strtolower($linha), 'data do recebimento') && str_contains(strtolower($linha), 'taxa mdr')) {
                $iniciouLeitura = true;
                $delimitador = ',';
                continue;
            }
            // 3. Extrato CIELO - RECEBIMENTOS
            if (str_contains($linha, 'Data de pagamento') && str_contains($linha, 'Bandeira') && !str_contains($linha, 'Filtros:')) {
                $iniciouLeitura = true;
                $delimitador = str_contains($linha, ';') ? ';' : ',';
                $tipoArquivoCielo = 'recebimento';
                continue;
            }
            // 4. Extrato CIELO - VENDAS
            if (str_contains($linha, 'Data da venda') && str_contains($linha, 'Hora da venda') && !str_contains($linha, 'Filtros:')) {
                $iniciouLeitura = true;
                $delimitador = str_contains($linha, ';') ? ';' : ',';
                $tipoArquivoCielo = 'venda';
                continue;
            }

            if (!$iniciouLeitura) {
                continue;
            }

            // Quebra a linha nas colunas exatas
            $dados = str_getcsv($linha, $delimitador);

            if (count($dados) < 5 || empty($dados[0])) {
                continue;
            }

            $dataStr = '';
            $descricao = '';
            $valorBruto = 0;
            $valorLiquido = 0;
            $tipo = 'entrada'; // Default para maquininhas

            // ==========================================
            // LÓGICA DE EXTRAÇÃO POR TIPO DE ARQUIVO
            // ==========================================

            // --- A. LEITURA DE EXTRATO BANCÁRIO ---
            if ($origem === 'banco') {
                $dataStr = trim($dados[0]);
                $descricaoRaw = strtoupper(trim($dados[1]));

                if (str_contains($descricaoRaw, 'SALDO') || str_contains($descricaoRaw, 'S A L D O')) {
                    continue;
                }

                $valorReal = $this->limparValorMoeda($dados[4] ?? '0');
                if ($valorReal == 0) continue;

                $tipo = str_contains(trim($dados[4]), '-') ? 'saida' : 'entrada';
                $valorBruto = $valorReal;
                $valorLiquido = $valorReal;

                $nomeCliente = !empty($dados[2]) ? ' - ' . trim($dados[2]) : '';
                $descricao = $descricaoRaw . $nomeCliente;

            // --- B. LEITURA DE MAQUININHAS ---
            } elseif ($origem === 'maquininha_recebidos' || $origem === 'maquininha_vendas') {

                if ($instituicao === 'rede' || $instituicao === 'itau') {
                    // Estrutura REDE
                    if (count($dados) < 23) continue;

                    $dataStr = trim($dados[0]);
                    $bandeira = strtoupper(trim($dados[21] ?? ''));
                    $modalidade = strtoupper(trim($dados[20] ?? ''));
                    $parcelas = trim($dados[22] ?? '1');

                    $descricao = "$bandeira $modalidade - PARC $parcelas";
                    $valorBruto = $this->limparValorMoeda($dados[3] ?? '0');
                    $valorLiquido = $this->limparValorMoeda($dados[7] ?? '0');

                } elseif ($instituicao === 'cielo') {
                    // Estrutura CIELO (Dinâmica)
                    $dataStr = trim($dados[0]);

                    if ($tipoArquivoCielo === 'venda') {
                        $bandeira = strtoupper(trim($dados[4] ?? '')); // Coluna 4
                        $formaPagamento = strtoupper(trim($dados[3] ?? '')); // Coluna 3

                        // Na Venda Cielo, a parcela vem escrita no nome (ex: "Crédito parcelado loja 02x")
                        preg_match('/\d+/', $formaPagamento, $matches);
                        $parcelas = !empty($matches[0]) ? (int)$matches[0] : 1;

                        $valorBruto = $this->limparValorMoeda($dados[5] ?? '0'); // Coluna 5
                        $valorLiquido = $this->limparValorMoeda($dados[7] ?? '0'); // Coluna 7
                    } else {
                        // CIELO RECEBIMENTOS
                        $bandeira = strtoupper(trim($dados[5] ?? '')); // Coluna 5
                        $formaPagamento = strtoupper(trim($dados[4] ?? '')); // Coluna 4
                        $parcelas = trim($dados[28] ?? '1'); // Coluna 28

                        $valorBruto = $this->limparValorMoeda($dados[6] ?? '0'); // Coluna 6
                        $valorLiquido = $this->limparValorMoeda($dados[8] ?? '0'); // Coluna 8
                    }

                    $descricao = "$bandeira $formaPagamento - PARC $parcelas";
                }

                if ($valorBruto == 0 && $valorLiquido == 0) continue;

            } else {
                continue;
            }

            // ==========================================
            // TRATAMENTO DA DATA E SALVAMENTO
            // ==========================================
            try {
                $dataTransacao = Carbon::createFromFormat('d/m/Y', $dataStr)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }

            $categoria = 'Outras Despesas';
            if ($tipo == 'entrada') {
                $categoria = 'Receitas de Vendas';
            } elseif (str_contains($descricao, 'TARIFA') || str_contains($descricao, 'MENSALIDADE')) {
                $categoria = 'Despesas Bancárias';
            } elseif (str_contains($descricao, 'IMPOSTO') || str_contains($descricao, 'DAS') || str_contains($descricao, 'DARF')) {
                $categoria = 'Impostos';
            }

            Conciliacao::create([
                'origem' => $origem,
                'conta_instituicao' => $instituicao,
                'mes_referencia' => (int) $mes,
                'ano_referencia' => (int) $ano,
                'data_transacao' => $dataTransacao,
                'descricao' => $descricao,
                'valor' => $valorLiquido,
                'valor_bruto' => $valorBruto,
                'tipo' => $tipo,
                'categoria' => $categoria
            ]);

            $registrosProcessados++;
        }

        return $registrosProcessados;
    }

    private function limparValorMoeda($valorStr)
    {
        $valorStr = trim(str_replace(['R$', '"', ' ', "\t"], '', $valorStr));
        if (str_contains($valorStr, ',')) {
            $valorStr = str_replace('.', '', $valorStr);
            $valorStr = str_replace(',', '.', $valorStr);
        }
        return abs((float) $valorStr);
    }
}
