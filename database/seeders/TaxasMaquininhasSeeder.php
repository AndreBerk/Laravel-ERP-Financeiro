<?php

namespace Database\Seeders;

use App\Models\TaxaMaquininha;
use Illuminate\Database\Seeder;

/**
 * Taxas contratuais da maquininha Rede (Itaú).
 * Rodar: php artisan db:seed --class=TaxasMaquininhasSeeder
 * Idempotente: rodar de novo só atualiza as taxas, não duplica.
 */
class TaxasMaquininhasSeeder extends Seeder
{
    private const INSTITUICAO = 'itau'; // mesmo valor da tela "Itaú (Rede)"

    /** Tabela padrão (blocos 2 e 3 + Elo). */
    private const PADRAO = ['debito' => 1.57, 'vista' => 2.81, '2a6' => 3.23, '7a21' => 3.49];

    /** bandeira => taxas (null = modalidade não aceita). */
    private function tabela(): array
    {
        $reduzida = ['debito' => 0.77, 'vista' => 2.01, '2a6' => 2.43, '7a21' => 2.69]; // bloco 4: Master/Visa

        return [
            // Blocos 1 e 4
            'mastercard' => $reduzida,
            'visa'       => $reduzida,
            'elo'        => self::PADRAO,
            'amex'       => ['debito' => null] + self::PADRAO, // Amex não tem débito
            // Bloco 2
            'diners'     => self::PADRAO,
            'cabal'      => self::PADRAO,
            'sicredi'    => self::PADRAO,
            'sorocred'   => self::PADRAO,
            // Bloco 3
            'cup'        => self::PADRAO,
            'banescard'  => self::PADRAO,
            'jcb'        => self::PADRAO,
            'credz'      => self::PADRAO,
        ];
    }

    public function run(): void
    {
        foreach ($this->tabela() as $bandeira => $t) {
            $faixas = [
                ['debito',            1, 1,  $t['debito']],
                ['credito_vista',     1, 1,  $t['vista']],
                ['credito_parcelado', 2, 6,  $t['2a6']],
                ['credito_parcelado', 7, 21, $t['7a21']],
            ];

            foreach ($faixas as [$modalidade, $de, $ate, $taxa]) {
                if ($taxa === null) continue;

                TaxaMaquininha::updateOrCreate(
                    [
                        'instituicao' => self::INSTITUICAO,
                        'bandeira'    => $bandeira,
                        'modalidade'  => $modalidade,
                        'parcela_de'  => $de,
                        'parcela_ate' => $ate,
                    ],
                    ['taxa_percentual' => $taxa]
                );
            }
        }
    }
}
