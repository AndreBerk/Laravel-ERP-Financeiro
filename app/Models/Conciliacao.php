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
        'valor_bruto', 'taxa_cobrada_percentual', 'taxa_acordada_percentual', 'divergencia_taxa'
    ];
}
