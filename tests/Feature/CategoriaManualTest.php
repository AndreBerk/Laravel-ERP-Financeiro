<?php

namespace Tests\Feature;

use App\Models\Conciliacao;
use App\Models\PoteConfiguracao;
use App\Models\User;
use App\Services\LeitorArquivoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaManualTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        (new PoteConfiguracao())->forceFill(['chaves_lucro' => 'PIX JOAO', 'chaves_investimento' => 'INVESTIMENTOS'])->save();

        $csv = "Data,Lançamento,Razão Social,CNPJ,Valor\n"
            . "05/03/2026,PIX RECEBIDO,CLIENTE A,,\"10000,00\"\n"
            . "05/03/2026,TED RECEBIDA,CLIENTE B,,\"500,00\"\n"
            . "06/03/2026,PIX ENVIADO,JOAO SILVA,,\"-2000,00\"\n"
            . "07/03/2026,BOLETO,ALFA INVESTIMENTOS SA,,\"-800,00\"\n"
            . "09/03/2026,ALUGUEL LOJA,,,\"-3000,00\"\n";
        $f = tempnam(sys_get_temp_dir(), 'ext');
        file_put_contents($f, $csv);
        app(LeitorArquivoService::class)->processar($f, 'banco', 'itau', 3, 2026);
        unlink($f);
    }

    private function linha(string $trecho): Conciliacao
    {
        return Conciliacao::where('descricao', 'like', "%{$trecho}%")->firstOrFail();
    }

    private function mudarCategoria(Conciliacao $l, string $categoria)
    {
        return $this->actingAs($this->user)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->patchJson(route('importacao.categoria', $l->id), ['categoria' => $categoria]);
    }

    public function test_importacao_usa_categorias_oficiais(): void
    {
        $this->assertSame('PIX', $this->linha('CLIENTE A')->categoria);
        $this->assertSame('Recebidos', $this->linha('CLIENTE B')->categoria);
        $this->assertSame(Conciliacao::POTE_LUCRO, $this->linha('JOAO')->categoria);
        // falso positivo da palavra-chave: fornecedor "INVESTIMENTOS SA"
        $this->assertSame(Conciliacao::POTE_INVESTIMENTO, $this->linha('ALFA')->categoria);
    }

    public function test_patch_corrige_falso_positivo_e_marca_manual(): void
    {
        $l = $this->linha('ALFA');
        $this->mudarCategoria($l, 'Despesas variáveis')->assertOk()->assertJson(['ok' => true, 'categoria' => 'Despesas variáveis']);

        $l->refresh();
        $this->assertSame('Despesas variáveis', $l->categoria);
        $this->assertTrue($l->categoria_manual);

        // reclassificação automática não desfaz a escolha manual
        app(LeitorArquivoService::class)->reclassificarExtratos();
        $this->assertSame('Despesas variáveis', $l->fresh()->categoria);
    }

    public function test_patch_rejeita_categoria_de_outro_tipo_e_desconhecida(): void
    {
        $saida = $this->linha('ALUGUEL');
        $this->mudarCategoria($saida, 'PIX')->assertUnprocessable();
        $this->mudarCategoria($saida, 'Qualquer coisa')->assertUnprocessable();
        $this->mudarCategoria($this->linha('CLIENTE A'), Conciliacao::POTE_LUCRO)->assertUnprocessable();
        $this->assertSame('Outras Despesas', $saida->fresh()->categoria);
    }

    public function test_patch_exige_login_e_linha_bancaria(): void
    {
        $l = $this->linha('ALUGUEL');
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->patchJson(route('importacao.categoria', $l->id), ['categoria' => 'Despesas fixas'])->assertUnauthorized();

        $maq = Conciliacao::create(['origem' => 'maquininha_vendas', 'conta_instituicao' => 'rede', 'mes_referencia' => 3, 'ano_referencia' => 2026,
            'data_transacao' => '2026-03-05', 'descricao' => 'VISA', 'tipo' => 'entrada', 'valor' => 10, 'categoria' => 'Recebidos']);
        $this->mudarCategoria($maq, 'PIX')->assertNotFound();
    }

    public function test_dre_respeita_categorias_manuais_e_legado(): void
    {
        $this->mudarCategoria($this->linha('ALFA'), 'Despesas variáveis')->assertOk();
        $this->mudarCategoria($this->linha('ALUGUEL'), 'Despesas fixas')->assertOk();
        Conciliacao::create(['origem' => 'banco', 'conta_instituicao' => 'itau', 'mes_referencia' => 3, 'ano_referencia' => 2026,
            'data_transacao' => '2026-03-11', 'descricao' => 'LEGADO', 'tipo' => 'saida', 'valor' => 100, 'categoria' => 'Aporte Investimento']);

        $d = $this->actingAs($this->user)->get(route('dre.index', ['mes' => 3, 'ano' => 2026]))->assertOk()->viewData('receitaCaixa');
        $this->assertEquals(10500, $d);

        $v = $this->get(route('dre.index', ['mes' => 3, 'ano' => 2026]));
        $this->assertEquals(3800, $v->viewData('saidasBanco'));            // fixas 3000 + variáveis 800
        $this->assertEquals(6700, $v->viewData('resultadoOperacional'));
        $this->assertEquals(2000, $v->viewData('destLucro'));
        $this->assertEquals(100, $v->viewData('destInvestimento'));         // categoria legada
        $this->assertEquals(4600, $v->viewData('lucroLiquido'));
        $nomes = collect($v->viewData('categoriasDespesas'))->pluck('categoria_nome')->all();
        $this->assertEqualsCanonicalizing(['Despesas fixas', 'Despesas variáveis'], $nomes);
    }

    public function test_tela_de_detalhes_mostra_select_agrupado(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('importacao.index', ['ver_detalhes' => 1, 'origem' => 'banco', 'conta' => 'itau', 'mes' => 3, 'ano' => 2026]))
            ->assertOk()->getContent();

        $this->assertStringContainsString('categoriaLinha(', $html);
        $this->assertStringContainsString('<optgroup label="Potes">', $html);
        $this->assertStringContainsString('<optgroup label="Operacional">', $html);
        $this->assertStringContainsString('<option value="Recebidos">', $html);
    }
}
