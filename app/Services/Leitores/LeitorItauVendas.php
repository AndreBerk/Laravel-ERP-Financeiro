<?php

namespace App\Services\Leitores;

use App\Models\Conciliacao;
use App\Models\TaxaMaquininha;

class LeitorItauVendas implements LeitorInterface
{
    public function ler($caminhoArquivo, $tipoArquivo, $instituicao, $mes, $ano)
    {
        ini_set('auto_detect_line_endings', TRUE);
        $registrosProcessados = 0;

        // Puxa todas as taxas dessa instituição do banco de dados para a memória (evita lentidão)
        $regrasTaxas = TaxaMaquininha::where('instituicao', $instituicao)->get();

        if (($handle = fopen($caminhoArquivo, "r")) !== FALSE) {

            // 1. DESCOBRE O SEPARADOR
            $textoAmostra = '';
            for($i=0; $i<15; $i++) {
                $linhaTexto = fgets($handle);
                if($linhaTexto) $textoAmostra .= $linhaTexto;
            }
            $separador = substr_count($textoAmostra, ';') > substr_count($textoAmostra, ',') ? ';' : ',';
            rewind($handle);

            // 2. CAÇADOR DE CABEÇALHO
            $cabecalho = false;
            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {
                if(count($linha) < 4) continue;
                $linhaStr = strtolower(implode(" ", $linha));
                if (strpos($linhaStr, 'data') !== false && strpos($linhaStr, 'valor') !== false) {
                    $cabecalho = array_map('strtolower', array_map('trim', $linha));
                    break;
                }
            }

            if (!$cabecalho) return 0;

            // 3. MAPEA AS COLUNAS (Agora caçando também parcelas, bandeira e taxas)
            $idxData = -1; $idxStatus = -1; $idxModalidade = -1; $idxValorLiquido = -1;
            $idxValorBruto = -1; $idxBandeira = -1; $idxParcelas = -1; $idxTaxaCobrada = -1;

            foreach ($cabecalho as $index => $col) {
                $col = preg_replace('/[^\p{L}\p{N}\s]/u', '', $col); // Limpa sujeiras

                if ($idxData === -1 && strpos($col, 'data') !== false) $idxData = $index;
                if ($idxStatus === -1 && strpos($col, 'status') !== false) $idxStatus = $index;
                if ($idxModalidade === -1 && (strpos($col, 'modalidade') !== false || strpos($col, 'produto') !== false)) $idxModalidade = $index;

                if (strpos($col, 'atualizado') !== false || strpos($col, 'liquido') !== false) $idxValorLiquido = $index;
                if ($idxValorBruto === -1 && strpos($col, 'original') !== false) $idxValorBruto = $index;
                if ($idxBandeira === -1 && strpos($col, 'bandeira') !== false) $idxBandeira = $index;
                if ($idxParcelas === -1 && strpos($col, 'parcelas') !== false) $idxParcelas = $index;
                if ($idxTaxaCobrada === -1 && (strpos($col, 'taxas descontadas') !== false || strpos($col, 'taxa mdr') !== false)) $idxTaxaCobrada = $index;
            }

            // Fallback se não achar o Bruto
            if ($idxValorBruto === -1) $idxValorBruto = $idxValorLiquido;

            if ($idxData === -1 || $idxValorLiquido === -1) return 0;

            // 4. LÊ AS VENDAS E CRUZA AS TAXAS
            while (($linha = fgetcsv($handle, 4000, $separador)) !== FALSE) {
                if (!isset($linha[$idxData]) || !isset($linha[$idxValorLiquido])) continue;

                $dataCrua = trim($linha[$idxData]);
                if (empty($dataCrua) || strlen($dataCrua) < 8) continue;

                if ($idxStatus !== -1) {
                    $status = strtolower(trim($linha[$idxStatus] ?? ''));
                    if(in_array($status, ['negada', 'expirado', 'cancelada', 'recusada'])) continue;
                }

                $modalidadeOriginal = $idxModalidade !== -1 ? strtolower(trim($linha[$idxModalidade] ?? '')) : 'maquininha';
                $bandeira = $idxBandeira !== -1 ? strtolower(trim($linha[$idxBandeira] ?? '')) : '';
                $parcelas = $idxParcelas !== -1 ? (int) trim($linha[$idxParcelas] ?? '1') : 1;
                if ($parcelas < 1) $parcelas = 1;

                // Mapeamento Inteligente da Modalidade para o Banco
                $modMapeada = 'credito_vista';
                if (strpos($modalidadeOriginal, 'bito') !== false) {
                    $modMapeada = 'debito';
                } elseif (strpos($modalidadeOriginal, 'pix') !== false) {
                    $modMapeada = 'pix';
                } elseif (strpos($modalidadeOriginal, 'dito') !== false || strpos($modalidadeOriginal, 'credito') !== false) {
                    $modMapeada = $parcelas > 1 ? 'credito_parcelado' : 'credito_vista';
                }

                // Limpa os valores monetários
                $valorLiquido = (float) str_replace(['R$', '.', ','], ['', '', '.'], trim($linha[$idxValorLiquido] ?? '0'));
                $valorBruto = (float) str_replace(['R$', '.', ','], ['', '', '.'], trim($linha[$idxValorBruto] ?? '0'));

                if ($valorBruto <= 0) continue;

                // Captura a taxa que o banco cobrou (limpando o % e a vírgula)
                $taxaCobrada = 0;
                if ($idxTaxaCobrada !== -1) {
                    $taxaCobradaCrua = str_replace(['%', ' '], '', trim($linha[$idxTaxaCobrada] ?? '0'));
                    $taxaCobrada = (float) str_replace(',', '.', $taxaCobradaCrua);
                }

                // CRUZAMENTO: Busca a taxa acordada no Cofre
                $taxaAcordada = 0;
                $regraEncontrada = $regrasTaxas->where('modalidade', $modMapeada)
                    ->where('parcela_de', '<=', $parcelas)
                    ->where('parcela_ate', '>=', $parcelas)
                    ->filter(function($regra) use ($bandeira) {
                        return empty($regra->bandeira) || $regra->bandeira === 'todas' || strpos($bandeira, strtolower($regra->bandeira)) !== false;
                    })->first();

                if ($regraEncontrada) {
                    $taxaAcordada = (float) $regraEncontrada->taxa_percentual;
                }

                // Margem de tolerância de 0.01% por conta de arredondamentos
                $divergencia = abs($taxaCobrada - $taxaAcordada) > 0.01;

                Conciliacao::create([
                    'origem' => 'maquininha_vendas',
                    'conta_instituicao' => $instituicao,
                    'mes_referencia' => (int)$mes,
                    'ano_referencia' => (int)$ano,
                    'data_transacao' => $this->formatarDataBrParaEua($dataCrua),
                    'descricao' => "Venda " . strtoupper($bandeira) . " - " . strtoupper($modMapeada) . " ({$parcelas}x)",
                    'tipo' => 'entrada',
                    'valor' => $valorLiquido,
                    'categoria' => 'Vendas Brutas (Comparativo)',
                    'enviado_dre' => true,
                    // Novos campos de Auditoria!
                    'valor_bruto' => $valorBruto,
                    'taxa_cobrada_percentual' => $taxaCobrada,
                    'taxa_acordada_percentual' => $taxaAcordada,
                    'divergencia_taxa' => $divergencia
                ]);

                $registrosProcessados++;
            }
            fclose($handle);
        }

        return $registrosProcessados;
    }

    private function formatarDataBrParaEua($data) {
        if(strpos($data, '/') !== false) {
            $partes = explode('/', trim($data));
            if(count($partes) >= 3) {
                $ano = explode(' ', $partes[2])[0];
                return $ano . '-' . $partes[1] . '-' . $partes[0];
            }
        }
        return date('Y-m-d');
    }
}
