<?php

namespace Tests\Feature;

use App\Models\Conciliacao;
use App\Models\SaldoInicial;
use App\Models\User;
use App\Services\LeitorArquivoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtratoSaldoTest extends TestCase
{
    use RefreshDatabase;

    private function importar(string $csv, string $banco): void
    {
        $f = tempnam(sys_get_temp_dir(), 'ext');
        file_put_contents($f, $csv);
        app(LeitorArquivoService::class)->processar($f, 'banco', $banco, 9, 2026);
        unlink($f);
    }

    public function test_bradesco_credito_e_debito_separados(): void
    {
        $this->importar(mb_convert_encoding(
            "Extrato de: Agência: 1234 Conta: 5678\n"
            . "Data;Lançamento;Dcto.;Crédito (R$);Débito (R$);Saldo (R$)\n"
            . "01/09/2026;SALDO ANTERIOR;;;;1.000,00\n"
            . "02/09/2026;PIX RECEBIDO CLIENTE;123;1.500,50;;2.500,50\n"
            . "03/09/2026;PAGTO BOLETO;456;;-300,00;2.200,50\n"
            . "Total;;;1.500,50;-300,00;\n", 'ISO-8859-1', 'UTF-8'), 'bradesco');

        $this->assertSame(1, Conciliacao::where('tipo', 'entrada')->where('valor', 1500.50)->count());
        $this->assertSame(1, Conciliacao::where('tipo', 'saida')->where('valor', 300)->count());
        $this->assertSame(2, Conciliacao::count());
    }

    public function test_itau_valor_unico_com_sinal(): void
    {
        $this->importar("Data;Lançamento;Ag./Origem;Valor (R$);Saldos (R$)\n"
            . "01/09/2026;SALDO ANTERIOR;;;800,00\n"
            . "02/09/2026;PIX TRANSF MARIA;0001;250,00;\n"
            . "03/09/2026;TAR PACOTE;;-49,90;\n", 'itau');

        $this->assertSame(['entrada', 'saida'], Conciliacao::orderBy('data_transacao')->pluck('tipo')->all());
        $this->assertEquals(250, Conciliacao::where('tipo', 'entrada')->value('valor'));
    }

    public function test_crud_saldo_inicial(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('configuracoes.saldo.salvar'), ['conta_instituicao' => 'itau', 'mes_referencia' => 9, 'ano_referencia' => 2026, 'valor' => 'R$ 48.979,76'])->assertRedirect();
        $saldo = SaldoInicial::firstOrFail();
        $this->assertEquals(48979.76, $saldo->valor);

        $this->get(route('configuracoes.index'))->assertOk()->assertSee('48.979,76')->assertSee('09/2026');

        $this->put(route('configuracoes.saldo.atualizar', $saldo), ['conta_instituicao' => 'bradesco', 'mes_referencia' => 10, 'ano_referencia' => 2026, 'valor' => '1.000,00'])->assertSessionHasNoErrors();
        $this->assertEquals(['bradesco', 10, 1000.0], [$saldo->fresh()->conta_instituicao, (int) $saldo->fresh()->mes_referencia, (float) $saldo->fresh()->valor]);

        $this->put(route('configuracoes.saldo.atualizar', $saldo), ['conta_instituicao' => 'bradesco', 'mes_referencia' => 10, 'ano_referencia' => 2026, 'valor' => 'abc'])->assertSessionHasErrors('valor');

        $this->delete(route('configuracoes.saldo.excluir', $saldo))->assertRedirect();
        $this->assertSame(0, SaldoInicial::count());
    }
}
