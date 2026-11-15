<?php

namespace Tests\Feature;

use App\Models\Conciliacao;
use App\Services\Leitores\LeitorItauVendas;
use App\Services\LeitorArquivoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeVendasTest extends TestCase
{
    use RefreshDatabase;

    /** Relatório de VENDAS da Rede: separador ";", "valor líquido" na coluna 16, depois do "valor da venda atualizado". */
    private function csvVendas(): string
    {
        $cab = ['data da venda', 'hora da venda', 'status da venda', 'valor da venda original', 'valor da venda atualizado',
            'tipo de captura', 'NSU/CV', 'TID', 'número do pedido', 'número da autorização', 'resumo de vendas',
            'nome do estabelecimento', 'estabelecimento', 'número do cartão', 'taxa MDR', 'valor MDR', 'valor líquido',
            'modalidade', 'bandeira', 'número de parcelas'];
        $linha = fn ($data, $status, $bruto, $liq, $mod, $band, $parc) => implode(';', [
            $data, '10:00', $status, $bruto, $bruto, 'maquininha', '1', '-', '-', '1', '1', 'LOJA', '1', '4493******0811',
            '0,77%', 'R$ 0,77', $liq, $mod, $band, $parc]);

        return "\u{FEFF}\"Relatório de vendas\nPERÍODO: 01-10-2026 A 31-10-2026\";;;\n\n"
            . implode(';', $cab) . "\n"
            . $linha('01/10/2026', 'aprovada', 'R$ 336,00', 'R$ 333,41', 'Débito', 'Visa', '1') . "\n"
            . $linha('02/10/2026', 'aprovada', 'R$ 1.200,00', 'R$ 1.161,24', 'Crédito', 'Mastercard', '10') . "\n"
            . $linha('03/10/2026', 'cancelada', 'R$ 50,00', 'R$ 49,00', 'Crédito', 'Elo', '1') . "\n";
    }

    private function arquivo(string $conteudo): string
    {
        $f = tempnam(sys_get_temp_dir(), 'rede');
        file_put_contents($f, $conteudo);
        return $f;
    }

    public function test_service_le_relatorio_de_vendas_com_liquido_correto(): void
    {
        $n = app(LeitorArquivoService::class)->processar($this->arquivo($this->csvVendas()), 'maquininha_vendas', 'itau', 10, 2026);

        $this->assertSame(2, $n);
        $v = Conciliacao::orderBy('data_transacao')->get();
        $this->assertEquals([333.41, 1161.24], $v->pluck('valor')->map(fn ($x) => (float) $x)->all());
        $this->assertEquals([336.00, 1200.00], $v->pluck('valor_bruto')->map(fn ($x) => (float) $x)->all());
        $this->assertSame('MASTERCARD CRÉDITO - PARC 10', $v[1]->descricao);
    }

    public function test_service_continua_lendo_relatorio_de_recebimentos(): void
    {
        $csv = "data do recebimento,data original da venda,data original de vencimento,valor bruto da parcela original,valor bruto da parcela atualizada,taxa MDR,valor MDR descontado,valor líquido da parcela,negociada,%,NSU/CV,TID,número do pedido,número da autorização,resumo de vendas/número do lote,nome do estabelecimento,estabelecimento,número do cartão,indicador de transação tokenizada,código IATA,modalidade,bandeira,número de parcelas,parcela,banco,agência,conta-corrente,cancelamento/contestação,data do cancelamento,status\n"
            . "02/10/2026,01/10/2026,02/10/2026,\"R$ 336,00\",-,\"0,77%\",\"R$ 2,59\",\"R$ 333,41\",-,\"100,0000%\",1,-,-,1,1,LOJA,1,4493******0811,não,-,Débito,Visa,1,1,BCO,1,1,-,-,pago\n";

        $this->assertSame(1, app(LeitorArquivoService::class)->processar($this->arquivo($csv), 'maquininha_recebidos', 'itau', 10, 2026));
        $this->assertEquals([333.41, 336.0], [(float) Conciliacao::first()->valor, (float) Conciliacao::first()->valor_bruto]);
    }

    public function test_leitor_itau_vendas_usa_coluna_liquido(): void
    {
        $n = (new LeitorItauVendas)->ler($this->arquivo($this->csvVendas()), 'maquininha_vendas', 'itau', 10, 2026);

        $this->assertSame(2, $n);
        $this->assertEquals([333.41, 1161.24], Conciliacao::orderBy('data_transacao')->pluck('valor')->map(fn ($x) => (float) $x)->all());
    }
}
