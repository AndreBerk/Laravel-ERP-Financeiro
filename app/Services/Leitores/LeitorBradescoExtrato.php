<?php

namespace App\Services\Leitores;

use App\Models\Conciliacao;

class LeitorBradescoExtrato implements LeitorInterface
{
    public function ler($caminhoArquivo, $tipoArquivo, $instituicao, $mes, $ano)
    {
        ini_set('auto_detect_line_endings', TRUE);
        $registrosProcessados = 0;

        if (($handle = fopen($caminhoArquivo, "r")) !== FALSE) {

            // 1. DESCOBRE O SEPARADOR
            $textoAmostra = '';
            for($i=0; $i<15; $i++) {
                $linhaTexto = fgets($handle);
                if($linhaTexto) $textoAmostra .= $linhaTexto;
            }
            $separador = substr_count($textoAmostra, ';') >= substr_count($textoAmostra, ',') ? ';' : ',';
            rewind($handle);

            // 2. CAÇADOR DE CABEÇALHO UNIVERSAL
            $cabecalho = false;
            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {
                if (count($linha) < 3) continue;

                $linhaLimpa = array_map([$this, 'limparTexto'], $linha);
                $linhaStr = implode(" ", $linhaLimpa);

                if (strpos($linhaStr, 'data') !== false &&
                   (strpos($linhaStr, 'credito') !== false || strpos($linhaStr, 'debito') !== false || strpos($linhaStr, 'lancamento') !== false)) {
                    $cabecalho = $linhaLimpa;
                    break;
                }
            }

            if (!$cabecalho) return 0;

            // 3. MAPEIA AS COLUNAS
            $idxData = -1; $idxDescricao = -1; $idxCredito = -1; $idxDebito = -1; $idxValorUnico = -1;

            foreach ($cabecalho as $index => $c) {
                if ($idxData === -1 && strpos($c, 'data') !== false) $idxData = $index;
                if ($idxDescricao === -1 && (strpos($c, 'lancamento') !== false || strpos($c, 'historico') !== false)) $idxDescricao = $index;
                if ($idxCredito === -1 && strpos($c, 'credito') !== false) $idxCredito = $index;
                if ($idxDebito === -1 && strpos($c, 'debito') !== false) $idxDebito = $index;
            }

            if ($idxCredito === -1 && $idxDebito === -1) {
                foreach ($cabecalho as $index => $c) {
                    if (strpos($c, 'valor') !== false) $idxValorUnico = $index;
                }
            }

            if ($idxData === -1) return 0;

            // 4. PROCESSA AS MOVIMENTAÇÕES
            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {

                // 🛑 FREIO DE MÃO PERFEITO: Se a linha começar por Total, para tudo!
                $coluna0 = strtolower(trim($linha[0] ?? ''));
                $colunaData = strtolower(trim($linha[$idxData] ?? ''));

                if ($coluna0 === 'total' || $colunaData === 'total') {
                    break;
                }

                if (!isset($linha[$idxData])) continue;

                $dataCrua = trim($linha[$idxData]);

                // Valida se é uma data (01/09/2026). Ignora linhas vazias ou cabeçalhos secundários.
                if (empty($dataCrua) || !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataCrua)) continue;

                $descricaoOriginal = $linha[$idxDescricao] ?? 'Movimentação';
                $descricaoOriginal = mb_convert_encoding($descricaoOriginal, 'UTF-8', mb_detect_encoding($descricaoOriginal, 'UTF-8, ISO-8859-1', true) ?: 'ISO-8859-1');
                $descricao = trim($descricaoOriginal);

                // Ignora apenas o "SALDO ANTERIOR" (porque não é uma movimentação real)
                if (strpos(strtolower($descricao), 'saldo anterior') !== false) continue;

                $valorFinal = 0;
                $tipoMovimentacao = 'entrada';

                if ($idxCredito !== -1 && $idxDebito !== -1) {
                    $credito = $this->extrairMoeda($linha[$idxCredito] ?? '');
                    $debito = $this->extrairMoeda($linha[$idxDebito] ?? '');

                    if ($credito > 0) {
                        $valorFinal = $credito;
                        $tipoMovimentacao = 'entrada';
                    } elseif ($debito > 0 || $debito < 0) {
                        $valorFinal = abs($debito);
                        $tipoMovimentacao = 'saida';
                    }
                } elseif ($idxValorUnico !== -1) {
                    $valorCru = $linha[$idxValorUnico] ?? '';
                    $isNegative = strpos($valorCru, '-') !== false;
                    $valor = $this->extrairMoeda($valorCru);

                    if ($valor > 0) {
                        $valorFinal = $valor;
                        $tipoMovimentacao = $isNegative ? 'saida' : 'entrada';
                    }
                }

                if ($valorFinal <= 0) continue;

                Conciliacao::create([
                    'origem' => 'banco',
                    'conta_instituicao' => $instituicao,
                    'mes_referencia' => (int)$mes,
                    'ano_referencia' => (int)$ano,
                    'data_transacao' => $this->formatarDataBrParaEua($dataCrua),
                    'descricao' => substr($descricao, 0, 255),
                    'tipo' => $tipoMovimentacao,
                    'valor' => $valorFinal,
                    'categoria' => 'Movimentação Bancária',
                    'enviado_dre' => false
                ]);

                $registrosProcessados++;
            }
            fclose($handle);
        }

        return $registrosProcessados;
    }

    private function limparTexto($texto) {
        $texto = strtolower(trim($texto));
        $texto = strtr(utf8_decode($texto), utf8_decode('áàãâéêíóôõúç'), 'aaaaeeiooouc');
        $texto = strtr($texto, 'áàãâéêíóôõúç', 'aaaaeeiooouc');
        return preg_replace('/[^a-z0-9]/', '', $texto);
    }

    private function extrairMoeda($string) {
        if (empty(trim($string))) return 0;

        $str = preg_replace('/[^\d.,-]/', '', $string);
        if (empty($str) || $str === '-') return 0;

        if (strpos($str, ',') !== false && strpos($str, '.') !== false) {
            if (strrpos($str, ',') > strrpos($str, '.')) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        } elseif (strpos($str, ',') !== false) {
            $str = str_replace(',', '.', $str);
        }

        return (float) $str;
    }

    private function formatarDataBrParaEua($data) {
        $partes = preg_split('/[\/\-\s]+/', trim($data));
        if(count($partes) >= 3) {
            $ano = strlen($partes[2]) == 2 ? '20' . $partes[2] : $partes[2];
            return $ano . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
        }
        return date('Y-m-d');
    }
}
