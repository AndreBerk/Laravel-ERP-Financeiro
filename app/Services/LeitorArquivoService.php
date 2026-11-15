<?php

namespace App\Services;

use App\Models\Conciliacao;
use App\Models\PoteConfiguracao;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LeitorArquivoService
{
    /** Pote => categoria gravada na conciliação (o DRE lê estas categorias). */
    public const CATEGORIAS_POTES = [
        'lucro' => Conciliacao::POTE_LUCRO,
        'reinvestimento' => Conciliacao::POTE_REINVESTIMENTO,
        'investimento' => Conciliacao::POTE_INVESTIMENTO,
    ];
    public const CATEGORIA_RESGATE = Conciliacao::RESGATE;

    /** Nomes antigos (antes da padronização) => pote. Mantidos para dados já gravados. */
    public const LEGADO_POTES = [
        'Distribuição de Lucros' => 'lucro',
        'Reinvestimento' => 'reinvestimento',
        'Aporte Investimento' => 'investimento',
        'Resgate Investimento' => 'investimento',
    ];

    /** "pix joão , Dividendos,," -> ['PIX JOAO', 'DIVIDENDOS'] */
    public static function listaChaves(?string $texto): array
    {
        $chaves = array_map(fn ($c) => Str::upper(Str::ascii(trim($c))), explode(',', (string) $texto));
        return array_values(array_unique(array_filter($chaves, fn ($c) => mb_strlen($c) >= 3)));
    }

    /** Palavras-chave configuradas na aba Configurações dos Potes, por pote. */
    private function chavesPotes(): array
    {
        $config = PoteConfiguracao::first();
        $chaves = [];
        foreach (array_keys(self::CATEGORIAS_POTES) as $pote) {
            $chaves[$pote] = self::listaChaves($config?->{'chaves_' . $pote});
        }
        return $chaves;
    }

    /**
     * Pote cuja palavra-chave aparece na descrição, ou null.
     * Chave com várias palavras casa quando TODAS aparecem, em qualquer ordem
     * ("PIX JOAO" casa com "PIX ENVIADO - JOAO SILVA").
     */
    private function identificarPote(string $descricao, array $chavesPotes): ?string
    {
        $desc = Str::upper(Str::ascii($descricao));
        foreach ($chavesPotes as $pote => $chaves) {
            foreach ($chaves as $chave) {
                $palavras = preg_split('/\s+/', $chave, -1, PREG_SPLIT_NO_EMPTY);
                $todas = true;
                foreach ($palavras as $palavra) {
                    if (!str_contains($desc, $palavra)) {
                        $todas = false;
                        break;
                    }
                }
                if ($todas && $palavras) {
                    return $pote;
                }
            }
        }
        return null;
    }

    /** Regra única de categoria automática (usada na importação e na reclassificação). */
    private function categorizar(string $origem, string $tipo, string $descricao, array $chavesPotes): string
    {
        $pote = $origem === 'banco' ? $this->identificarPote($descricao, $chavesPotes) : null;

        if ($pote !== null && $tipo === 'saida') {
            return self::CATEGORIAS_POTES[$pote];          // saída para um pote: destinação, não despesa
        }
        if ($pote === 'investimento' && $tipo === 'entrada') {
            return self::CATEGORIA_RESGATE;                 // dinheiro voltando da aplicação: não é venda
        }
        if ($tipo === 'entrada') {
            return str_contains($descricao, 'PIX') ? 'PIX' : 'Recebidos';
        }
        if (str_contains($descricao, 'TARIFA') || str_contains($descricao, 'MENSALIDADE')) {
            return 'Despesas Bancárias';
        }
        if (str_contains($descricao, 'IMPOSTO') || str_contains($descricao, 'DAS') || str_contains($descricao, 'DARF')) {
            return 'Impostos';
        }
        return 'Outras Despesas';
    }

    /**
     * Reaplica as palavras-chave atuais aos extratos bancários já importados.
     * Só mexe em linhas com categoria automática e nunca nas escolhidas à mão (categoria_manual).
     * Retorna quantas linhas mudaram de categoria.
     */
    public function reclassificarExtratos(): int
    {
        $automaticas = array_merge(
            ['Outras Despesas', 'Receitas de Vendas', 'PIX', 'Recebidos', 'Despesas Bancárias', 'Impostos', 'Movimentação Bancária', self::CATEGORIA_RESGATE],
            array_values(self::CATEGORIAS_POTES),
            array_keys(self::LEGADO_POTES)
        );
        $chaves = $this->chavesPotes();
        $alteradas = 0;

        Conciliacao::where('origem', 'banco')
            ->where('categoria_manual', false)
            ->where(fn ($q) => $q->whereIn('categoria', $automaticas)->orWhereNull('categoria'))
            ->select(['id', 'tipo', 'descricao', 'categoria'])
            ->chunkById(500, function ($linhas) use ($chaves, &$alteradas) {
                foreach ($linhas as $l) {
                    $nova = $this->categorizar('banco', (string) $l->tipo, Str::upper((string) $l->descricao), $chaves);
                    if ($nova !== $l->categoria) {
                        Conciliacao::whereKey($l->id)->update(['categoria' => $nova]);
                        $alteradas++;
                    }
                }
            });

        return $alteradas;
    }

    public function processar($caminhoArquivo, $origem, $instituicao, $mes, $ano)
    {
        $chavesPotes = $origem === 'banco' ? $this->chavesPotes() : [];

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
        $colunasBanco = null;
        $colunasRede = null;

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if (empty($linha)) continue;

            // ==========================================
            // DETECÇÃO INTELIGENTE DE CABEÇALHO
            // ==========================================

            // 1. Extrato bancário (Itaú: Valor único com sinal | Bradesco: Crédito e Débito separados)
            if ($origem === 'banco' && ($mapa = $this->mapearCabecalhoBanco($linha)) !== null) {
                [$colunasBanco, $delimitador] = $mapa;
                $iniciouLeitura = true;
                continue;
            }
            // 2. Extrato REDE (Itaú Laranjinha)
            //    Aceita o relatório de recebimentos ("data do recebimento") e o de vendas ("data da venda")
            if ($origem !== 'banco' && $instituicao !== 'cielo' && ($mapa = $this->mapearCabecalhoRede($linha)) !== null) {
                [$colunasRede, $delimitador] = $mapa;
                $iniciouLeitura = true;
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

            if (empty(trim($dados[0] ?? '')) || ($origem !== 'banco' && count($dados) < 5)) {
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
                $c = $colunasBanco;
                $dataStr = trim($dados[$c['data']] ?? '');
                $descricaoRaw = strtoupper(trim($dados[$c['descricao']] ?? ''));

                if (str_contains($descricaoRaw, 'SALDO') || str_contains($descricaoRaw, 'S A L D O')) {
                    continue;
                }

                if ($c['credito'] !== null || $c['debito'] !== null) {
                    // Colunas separadas: crédito = entrada, débito = saída
                    $credito = $c['credito'] !== null ? $this->limparValorMoeda($dados[$c['credito']] ?? '0') : 0;
                    $debito = $c['debito'] !== null ? $this->limparValorMoeda($dados[$c['debito']] ?? '0') : 0;
                    [$valorReal, $tipo] = $credito > 0 ? [$credito, 'entrada'] : [$debito, 'saida'];
                } else {
                    // Coluna única: sinal "-" (ou sufixo D) = saída
                    $bruto = trim($dados[$c['valor']] ?? '0');
                    $valorReal = $this->limparValorMoeda($bruto);
                    $tipo = (str_contains($bruto, '-') || preg_match('/D\s*$/i', $bruto)) ? 'saida' : 'entrada';
                }
                if ($valorReal == 0) continue;

                $valorBruto = $valorReal;
                $valorLiquido = $valorReal;

                $nome = $c['nome'] !== null ? trim($dados[$c['nome']] ?? '') : '';
                $descricao = $descricaoRaw . ($nome !== '' ? ' - ' . strtoupper($nome) : '');

            // --- B. LEITURA DE MAQUININHAS ---
            } elseif ($origem === 'maquininha_recebidos' || $origem === 'maquininha_vendas') {

                if ($instituicao === 'rede' || $instituicao === 'itau') {
                    // Estrutura REDE (colunas mapeadas pelo nome do cabeçalho)
                    if ($colunasRede === null) continue;
                    $r = $colunasRede;
                    $col = fn ($k, $padrao = '') => $r[$k] !== null ? trim($dados[$r[$k]] ?? $padrao) : $padrao;

                    $status = Str::lower(Str::ascii($col('status')));
                    if (preg_match('/negad|cancelad|recusad|expirad|desfeit|estornad/', $status)) continue;

                    $dataStr = substr($col('data'), 0, 10); // "01/10/2026 14:32" -> "01/10/2026"
                    $bandeira = mb_strtoupper($col('bandeira'));
                    $modalidade = mb_strtoupper($col('modalidade'));
                    $parcelas = max(1, (int) $col('parcelas', '1'));

                    $descricao = "$bandeira $modalidade - PARC $parcelas";
                    $valorBruto = $this->limparValorMoeda($col('bruto', '0'));
                    $valorLiquido = $r['liquido'] !== null ? $this->limparValorMoeda($col('liquido', '0')) : $valorBruto;

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

            $categoria = $this->categorizar($origem, $tipo, $descricao, $chavesPotes);

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

    /**
     * Reconhece o cabeçalho do extrato bancário e devolve [colunas, delimitador], ou null.
     * Aceita "Data;Lançamento;...;Crédito (R$);Débito (R$);Saldo (R$)" (Bradesco)
     * e "Data,Lançamento,Razão Social,CPF/CNPJ,Valor (R$),Saldo (R$)" (Itaú).
     */
    private function mapearCabecalhoBanco(string $linha): ?array
    {
        $delimitador = substr_count($linha, ';') >= substr_count($linha, ',') ? ';' : ',';
        $cols = array_map(fn ($c) => Str::lower(Str::ascii(trim($c))), str_getcsv($linha, $delimitador));

        $acha = function (array $termos, array $evitar = []) use ($cols) {
            foreach ($cols as $i => $c) {
                foreach ($evitar as $e) if (str_contains($c, $e)) continue 2;
                foreach ($termos as $t) if (str_contains($c, $t)) return $i;
            }
            return null;
        };

        $m = [
            'data' => $acha(['data']),
            'descricao' => $acha(['lancamento', 'historico', 'descricao']),
            'credito' => $acha(['credito']),
            'debito' => $acha(['debito']),
            'valor' => $acha(['valor'], ['saldo']),
            'nome' => $acha(['razao social', 'nome', 'favorecido', 'pagador']),
        ];

        if ($m['data'] !== 0 || $m['descricao'] === null) return null;
        if ($m['credito'] === null && $m['debito'] === null && $m['valor'] === null) return null;

        return [$m, $delimitador];
    }

    /**
     * Cabeçalho REDE/Itaú (recebimentos ou vendas) => [colunas, delimitador], ou null.
     * Nomes normalizados sem acento: "valor líquido" casa com "liquido".
     */
    private function mapearCabecalhoRede(string $linha): ?array
    {
        $delimitador = substr_count($linha, ';') > substr_count($linha, ',') ? ';' : ',';
        $cols = array_map(
            fn ($c) => Str::lower(Str::ascii(trim($c, " \t\"\u{FEFF}"))),
            str_getcsv(ltrim($linha, "\u{FEFF}"), $delimitador)
        );

        if (!str_contains($cols[0] ?? '', 'data do recebimento') && !str_contains($cols[0] ?? '', 'data da venda')) {
            return null;
        }

        $acha = function (callable $teste) use ($cols) {
            foreach ($cols as $i => $c) {
                if ($teste($c)) return $i;
            }
            return null;
        };
        $valor = fn ($c) => str_contains($c, 'valor') && !str_contains($c, 'mdr') && !str_contains($c, 'taxa');

        $m = [
            'data' => 0,
            'liquido' => $acha(fn ($c) => $valor($c) && str_contains($c, 'liquido')),
            // "valor bruto da parcela original" (recebimentos) | "valor da venda original" (vendas)
            'bruto' => $acha(fn ($c) => $valor($c) && !str_contains($c, 'liquido') && (str_contains($c, 'original') || str_contains($c, 'bruto')))
                ?? $acha(fn ($c) => $valor($c) && !str_contains($c, 'liquido')),
            'modalidade' => $acha(fn ($c) => str_contains($c, 'modalidade')),
            'bandeira' => $acha(fn ($c) => str_contains($c, 'bandeira')),
            'parcelas' => $acha(fn ($c) => str_contains($c, 'parcelas')),
            'status' => $acha(fn ($c) => str_contains($c, 'status')),
        ];

        return $m['bruto'] !== null && $m['bandeira'] !== null ? [$m, $delimitador] : null;
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
