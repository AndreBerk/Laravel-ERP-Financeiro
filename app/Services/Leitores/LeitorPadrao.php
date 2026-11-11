<?php

namespace App\Services\Leitores;

use App\Models\Conciliacao;

class LeitorPadrao implements LeitorInterface
{
    public function ler($caminhoArquivo, $tipoArquivo, $instituicao, $mes, $ano)
    {
        ini_set('auto_detect_line_endings', TRUE);
        $dicionarioCategorias = [
            'Água e Luz' => ['ENERGIA', 'CEMIG', 'ENEL', 'SABESP', 'COPASA', 'SANEPAR'],
            'Internet e Telefone' => ['VIVO', 'CLARO', 'TIM', 'ALGAR', 'FIBRA'],
            'Aluguel' => ['ALUGUEL', 'IMOVEIS'],
            'Impostos' => ['DARF', 'DAS', 'SIMPLES', 'GPS'],
            'Fornecedores' => ['DISTRIBUIDORA', 'ATACADAO', 'COMERCIO']
        ];

        $registrosProcessados = 0;

        if (($handle = fopen($caminhoArquivo, "r")) !== FALSE) {

            $primeiraLinha = fgets($handle);
            $separador = strpos($primeiraLinha, ';') !== false ? ';' : ',';
            rewind($handle);

            fgetcsv($handle, 0, $separador);

            while (($linha = fgetcsv($handle, 0, $separador)) !== FALSE) {
                if(count($linha) < 3) continue;

                $dataCrua = trim($linha[0] ?? '');
                $descricao = trim($linha[1] ?? 'Sem descrição');
                $valorCru = trim($linha[2] ?? '0');

                $valorLimpo = (float) str_replace(['R$', '.', ','], ['', '', '.'], $valorCru);
                if ($valorLimpo == 0) continue;

                $tipoTransacao = $valorLimpo > 0 ? 'entrada' : 'saida';
                $valorAbsoluto = abs($valorLimpo);

                $categoriaEncontrada = null;
                $descUpper = strtoupper($descricao);

                foreach ($dicionarioCategorias as $nomeCategoria => $palavrasChave) {
                    foreach ($palavrasChave as $palavra) {
                        if (strpos($descUpper, $palavra) !== false) {
                            $categoriaEncontrada = $nomeCategoria;
                            break 2;
                        }
                    }
                }

                Conciliacao::create([
                    'origem' => $tipoArquivo,
                    'conta_instituicao' => $instituicao,
                    'mes_referencia' => (int)$mes,
                    'ano_referencia' => (int)$ano,
                    'data_transacao' => $this->formatarDataBrParaEua($dataCrua),
                    'descricao' => $descricao,
                    'tipo' => $tipoTransacao,
                    'valor' => $valorAbsoluto,
                    'categoria' => $categoriaEncontrada,
                    'enviado_dre' => true
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
