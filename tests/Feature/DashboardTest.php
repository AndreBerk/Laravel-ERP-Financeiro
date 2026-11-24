<?php

namespace Tests\Feature;

use App\Models\Conciliacao;
use App\Models\SaldoInicial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function linha(array $d): void
    {
        Conciliacao::create($d + ['conta_instituicao' => 'itau', 'mes_referencia' => 11, 'ano_referencia' => 2026,
            'data_transacao' => '2026-11-05', 'descricao' => 'X', 'categoria' => 'Outras Despesas']);
    }

    public function test_metricas_do_mes(): void
    {
        Carbon::setTestNow('2026-11-16 10:00');
        $this->actingAs(User::factory()->create(['name' => 'Maria Souza']));

        SaldoInicial::create(['conta_instituicao' => 'itau', 'mes_referencia' => 11, 'ano_referencia' => 2026, 'valor' => 1000]);
        SaldoInicial::create(['conta_instituicao' => 'bradesco', 'mes_referencia' => 11, 'ano_referencia' => 2026, 'valor' => 500]);
        $this->linha(['origem' => 'banco', 'tipo' => 'entrada', 'valor' => 3000, 'descricao' => 'PIX RECEBIDO', 'categoria' => 'PIX']);
        $this->linha(['origem' => 'banco', 'tipo' => 'saida', 'valor' => 800, 'descricao' => 'ALUGUEL', 'data_transacao' => '2026-11-10']);
        $this->linha(['origem' => 'banco', 'tipo' => 'entrada', 'valor' => 999, 'mes_referencia' => 10, 'data_transacao' => '2026-10-20']); // outro mês
        $this->linha(['origem' => 'maquininha_vendas', 'tipo' => 'entrada', 'valor' => 4800, 'valor_bruto' => 5000, 'mes_referencia' => 9]);
        $this->linha(['origem' => 'maquininha_recebidos', 'tipo' => 'entrada', 'valor' => 1950, 'valor_bruto' => 2000]);

        $r = $this->get(route('dashboard'))->assertOk();

        $this->assertEquals(3000, $r->viewData('receitasMes'));
        $this->assertEquals(800, $r->viewData('despesasMes'));
        $this->assertEquals(3700, $r->viewData('saldoAtual'));          // 1500 + 3000 − 800
        $this->assertEquals(3000, $r->viewData('aReceberMaquininha'));  // 5000 − 2000
        $ultimas = $r->viewData('ultimasTransacoes');
        $this->assertCount(3, $ultimas);
        $this->assertSame('ALUGUEL', $ultimas->first()->descricao);     // mais recente primeiro
        $r->assertSee('Bom dia, Maria', false)->assertSee('R$ 3.700,00')->assertSee('+ R$ 3.000,00', false);
    }

    public function test_estado_vazio(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('dashboard'))->assertOk()->assertSee('Nenhuma movimentação ainda')->assertSee('Sem saldo inicial do mês');
    }
}
