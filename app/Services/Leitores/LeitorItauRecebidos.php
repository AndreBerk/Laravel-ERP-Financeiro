<?php

namespace App\Services\Leitores;

use App\Models\Conciliacao;

class LeitorItauRecebidos implements LeitorInterface
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
            $separador = substr_count($textoAmostra, ';') > substr_count($textoAmostra, ',') ? ';' : ',';
            rewind($handle);

            // 2. CAÇADOR DE CABEÇALHO ESTRITO
            $cabecalho = false;
            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {
                if(count($linha) < 4) continue;

                $linhaStr = strtolower(implode(" ", $linha));
                $linhaStr = str_replace(['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç'], ['a','a','a','a','e','e','i','o','o','o','u','c'], $linhaStr);

                // Exige que a linha tenha especificamente "data" e "liquido" ou "bruto" para ter a certeza que é o cabeçalho da tabela
                if (strpos($linhaStr, 'data') !== false && (strpos($linhaStr, 'liquido') !== false || strpos($linhaStr, 'bruto') !== false)) {
                    $cabecalho = array_map('trim', $linha);
                    break;
                }
            }

            if (!$cabecalho) return 0;

            // 3. MAPEA AS COLUNAS PELO NOME EXATO
            $idxData = -1; $idxValorLiquido = -1; $idxValorBruto = -1;
            $idxBanco = -1; $idxBandeira = -1; $idxModalidade = -1;

            foreach ($cabecalho as $index => $col) {
                $c = strtolower(trim($col));
                $c = str_replace(['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç'], ['a','a','a','a','e','e','i','o','o','o','u','c'], $c);
                $c = preg_replace('/[^a-z0-9]/', '', $c);

                // Data do pagamento
                if ($idxData === -1 && (strpos($c, 'pagamento') !== false || strpos($c, 'deposito') !== false || strpos($c, 'data') !== false)) {
                    $idxData = $index;
                }

                // Valor Bruto
                if ($idxValorBruto === -1 && (strpos($c, 'valorbruto') !== false || strpos($c, 'bruto') !== false)) {
                    $idxValorBruto = $index;
                }

                // Valor Líquido (Muito restrito)
                if ($idxValorLiquido === -1 && (strpos($c, 'valorliquido') !== false || strpos($c, 'liquido') !== false || strpos($c, 'depositado') !== false)) {
                    $idxValorLiquido = $index;
                }

                if ($idxBanco === -1 && strpos($c, 'banco') !== false) $idxBanco = $index;
                if ($idxBandeira === -1 && strpos($c, 'bandeira') !== false) $idxBandeira = $index;
                if ($idxModalidade === -1 && (strpos($c, 'modalidade') !== false || strpos($c, 'produto') !== false || strpos($c, 'meio') !== false)) $idxModalidade = $index;
            }

            // Fallbacks de segurança
            if ($idxValorLiquido === -1 && $idxValorBruto !== -1) $idxValorLiquido = $idxValorBruto;
            if ($idxValorBruto === -1 && $idxValorLiquido !== -1) $idxValorBruto = $idxValorLiquido;
            if ($idxData === -1 || $idxValorLiquido === -1) return 0;

            // 4. AGRUPADOR INTELIGENTE
            $depositosAgrupados = [];

            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {
                // Previne erros se a linha acabar a meio
                if (!isset($linha[$idxData]) || !isset($linha[$idxValorLiquido])) continue;

                $dataCrua = trim($linha[$idxData]);

                // Ignora tudo que não for uma data válida (protege contra totais no final da tabela)
                if (empty($dataCrua) || strlen($dataCrua) < 8 || !preg_match('/\d/', $dataCrua)) continue;

                $banco = $idxBanco !== -1 ? trim($linha[$idxBanco] ?? '') : 'Banco';
                $bandeira = $idxBandeira !== -1 ? trim($linha[$idxBandeira] ?? '') : '';
                $modalidade = $idxModalidade !== -1 ? trim($linha[$idxModalidade] ?? '') : '';

                // Extrai e limpa os valores, protegendo contra lixo de formatação
                $valorLiquido = $this->extrairMoeda($linha[$idxValorLiquido] ?? '0');
                $valorBruto = $this->extrairMoeda($linha[$idxValorBruto] ?? '0');

                if ($valorLiquido == 0 && $valorBruto == 0) continue;

                // Agrupa as transações para gerar o montante final do depósito do dia
                $chave = $dataCrua . '|' . $banco . '|' . $bandeira . '|' . $modalidade;

                if (!isset($depositosAgrupados[$chave])) {
                    $depositosAgrupados[$chave] = [
                        'data' => $dataCrua,
                        'banco' => $banco,
                        'bandeira' => $bandeira,
                        'modalidade' => $modalidade,
                        'valor_liquido' => 0,
                        'valor_bruto' => 0
                    ];
                }

                $depositosAgrupados[$chave]['valor_liquido'] += $valorLiquido;
                $depositosAgrupados[$chave]['valor_bruto'] += $valorBruto;
            }
            fclose($handle);

            // 5. SALVA NO BANCO DE DADOS
            foreach ($depositosAgrupados as $deposito) {
                $liquidoFinal = round($deposito['valor_liquido'], 2);
                $brutoFinal = round($deposito['valor_bruto'], 2);

                if ($liquidoFinal <= 0) continue;

                $descricaoExtra = trim("{$deposito['banco']} - {$deposito['bandeira']} {$deposito['modalidade']}");
                $descricaoExtra = empty($descricaoExtra) ? 'Diversos' : $descricaoExtra;
                $descricao = "Depósito " . strtoupper(trim($descricaoExtra, " -"));

                Conciliacao::create([
                    'origem' => 'maquininha_recebidos',
                    'conta_instituicao' => $instituicao,
                    'mes_referencia' => (int)$mes,
                    'ano_referencia' => (int)$ano,
                    'data_transacao' => $this->formatarDataBrParaEua($deposito['data']),
                    'descricao' => substr($descricao, 0, 255),
                    'tipo' => 'entrada',
                    'valor' => $liquidoFinal,
                    'valor_bruto' => $brutoFinal,
                    'categoria' => 'Recebimento Maquininha',
                    'enviado_dre' => true
                ]);

                $registrosProcessados++;
            }
        }

        return $registrosProcessados;
    }

    private function extrairMoeda($string) {
        if (empty(trim($string))) return 0;

        $isNegative = strpos($string, '-') !== false || strpos($string, 'D') !== false;

        // Remove R$, espaços e caracteres invisíveis
        $str = preg_replace('/[^\d.,]/', '', $string);

        if (empty($str)) return 0;

        // Trata pontos e vírgulas: se tiver os dois (ex: 1.234,56)
        if (strpos($str, ',') !== false && strpos($str, '.') !== false) {
            if (strrpos($str, ',') > strrpos($str, '.')) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        }
        // Se só tiver vírgula (ex: 1234,56)
        elseif (strpos($str, ',') !== false) {
            $str = str_replace(',', '.', $str);
        }

        $valor = (float) $str;
        return $isNegative ? -$valor : $valor;
    }

    private function formatarDataBrParaEua($data) {
        if(strpos($data, '/') !== false) {
            // Separa os dias, meses e anos
            $partes = preg_split('/[\/\-\s]+/', trim($data));
            if(count($partes) >= 3) {
                // Garante que o ano fica com 4 dígitos
                $ano = strlen($partes[2]) == 2 ? '20' . $partes[2] : $partes[2];
                return $ano . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            }
        }
        return date('Y-m-d');
    }
}
