<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conciliacao extends Model
{
    use HasFactory;

    // Esta linha força o Laravel a usar o nome correto da tabela em português!
    protected $table = 'conciliacoes';

    protected $fillable = [
        'origem', 'conta_instituicao', 'mes_referencia', 'ano_referencia',
        'data_transacao', 'descricao', 'tipo', 'valor', 'categoria', 'enviado_dre',
        'valor_bruto', 'taxa_cobrada_percentual', 'taxa_acordada_percentual', 'divergencia_taxa',
        'categoria_manual',
    ];

    protected $casts = ['categoria_manual' => 'boolean'];

    // Categorias oficiais (o DRE lê exatamente estes textos)
    public const RESGATE = 'Resgate / investimento';
    public const POTE_LUCRO = 'Lucro distribuído aos sócios';
    public const POTE_REINVESTIMENTO = 'Reinvestimento no negócio';
    public const POTE_INVESTIMENTO = 'Reserva / investimento';

    /** Opções do select por tipo, agrupadas (grupo => categorias). */
    public const OPCOES = [
        'entrada' => [
            'Receitas' => ['PIX', 'Recebidos', 'Outras Receitas'],
            'Potes' => [self::RESGATE],
        ],
        'saida' => [
            'Operacional' => ['Despesas fixas', 'Despesas variáveis'],
            'Potes' => [self::POTE_LUCRO, self::POTE_REINVESTIMENTO, self::POTE_INVESTIMENTO],
            'Padrão' => ['Outras Despesas'],
        ],
    ];

    public static function opcoesPlanas(string $tipo): array
    {
        return array_merge(...array_values(self::OPCOES[$tipo] ?? [[]]));
    }
}
