<?php

namespace Tests\Feature;

use App\Models\Conciliacao;
use App\Models\User;
use Database\Seeders\TaxasMaquininhasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AuditoriaTaxasTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_usa_faixas_do_seeder(): void
    {
        $this->seed(TaxasMaquininhasSeeder::class);
        $this->actingAs(User::factory()->create());

        // REDE: data, ..., [3]=bruto, ..., [7]=líquido, ..., [20]=modalidade, [21]=bandeira, [22]=parcelas
        $linha = fn ($bruto, $liq, $mod, $band, $parc) => implode(',', array_replace(array_fill(0, 23, 'x'),
            [0 => '05/09/2026', 3 => $bruto, 7 => $liq, 20 => $mod, 21 => $band, 22 => $parc]));
        $cab = array_fill(0, 23, 'x');
        $cab[0] = 'data do recebimento'; $cab[3] = 'valor bruto da parcela original'; $cab[5] = 'taxa MDR';
        $cab[7] = 'valor líquido da parcela'; $cab[20] = 'modalidade'; $cab[21] = 'bandeira'; $cab[22] = 'número de parcelas';
        $csv = implode(',', $cab) . "\n"
            . $linha('100.00', '99.23', 'debito', 'visa', '1') . "\n"         // 0,77% ok
            . $linha('100.00', '97.57', 'credito', 'elo', '4') . "\n"         // 2,43% < 3,23% ok
            . $linha('100.00', '96.00', 'credito', 'mastercard', '10') . "\n"; // 4% > 2,69% diverge

        $this->post(route('importacao.upload'), [
            'mes' => 9, 'ano' => 2026, 'tipo_arquivo' => 'maquininha_vendas', 'conta_maquininha' => 'itau',
            'arquivo_importacao' => UploadedFile::fake()->createWithContent('rede.csv', $csv),
        ])->assertSessionHasNoErrors();

        $v = Conciliacao::orderBy('id')->get();
        $this->assertCount(3, $v);
        $this->assertEquals([0.77, 3.23, 2.69], $v->pluck('taxa_acordada_percentual')->map(fn ($t) => (float) $t)->all());
        $this->assertEquals([false, false, true], $v->pluck('divergencia_taxa')->map(fn ($d) => (bool) $d)->all());
    }
}
