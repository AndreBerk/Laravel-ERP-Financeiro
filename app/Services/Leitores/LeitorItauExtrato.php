<?php

namespace App\Services\Leitores;

use App\Models\Conciliacao;

class LeitorItauExtrato implements LeitorInterface
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

            // 2. CAÇADOR DE CABEÇALHO DO ITAÚ
            $cabecalho = false;
            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {
                if (count($linha) < 3) continue;

                $linhaLimpa = array_map([$this, 'limparTexto'], $linha);
                $linhaStr = implode(" ", $linhaLimpa);

                // O Itaú usa Data | Lançamento | Valor (R$)
                if (strpos($linhaStr, 'data') !== false &&
                   (strpos($linhaStr, 'lancamento') !== false || strpos($linhaStr, 'historico') !== false) &&
                   strpos($linhaStr, 'valor') !== false) {
                    $cabecalho = $linhaLimpa;
                    break;
                }
            }

            if (!$cabecalho) return 0;

            // 3. MAPEIA AS COLUNAS
            $idxData = -1; $idxDescricao = -1; $idxValor = -1;

            foreach ($cabecalho as $index => $c) {
                if ($idxData === -1 && strpos($c, 'data') !== false) $idxData = $index;
                if ($idxDescricao === -1 && (strpos($c, 'lancamento') !== false || strpos($c, 'historico') !== false)) $idxDescricao = $index;
                if ($idxValor === -1 && strpos($c, 'valor') !== false) $idxValor = $index;
            }

            if ($idxData === -1 || $idxValor === -1) return 0;

            // 4. PROCESSA AS MOVIMENTAÇÕES
            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {

                if (!isset($linha[$idxData])) continue;

                $dataCrua = trim($linha[$idxData]);

                // Filtro 1: Exige uma data real (ignora títulos e lixo do Itaú)
                if (empty($dataCrua) || !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataCrua)) continue;

                $descricaoOriginal = $linha[$idxDescricao] ?? 'Movimentação';
                $descricaoOriginal = mb_convert_encoding($descricaoOriginal, 'UTF-8', mb_detect_encoding($descricaoOriginal, 'UTF-8, ISO-8859-1', true) ?: 'ISO-8859-1');
                $descricao = trim($descricaoOriginal);

                // 🚧 FILTROS DE LIXO DO ITAÚ
                $descLimpa = strtolower($descricao);
                if (strpos($descLimpa, 'saldo') !== false) continue; // Ignora SALDO ANTERIOR e SALDO DISPONÍVEL
                if (strpos($descLimpa, 'rendimento') !== false) continue; // Ignora centavos de rendimentos
                if (strpos($descLimpa, 'aplic aut') !== false) continue; // Aplicação automática
                if (strpos($descLimpa, 'resgate aut') !== false) continue;

                $valorCru = $linha[$idxValor] ?? '';
                // No Itaú, as saídas (PIX Enviado, Pagamentos) vêm com o sinal de Menos na frente
                $isNegative = strpos($valorCru, '-') !== false;

                $valorFinal = $this->extrairMoeda($valorCru);
                if ($valorFinal <= 0) continue;

                $tipoMovimentacao = $isNegative ? 'saida' : 'entrada';

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
