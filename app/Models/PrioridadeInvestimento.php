<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrioridadeInvestimento extends Model
{
    protected $table = 'prioridades_investimento';

    protected $fillable = [
        'titulo', 'categoria', 'prioridade', 'status',
        'valor_previsto', 'valor_gasto', 'prazo', 'observacao', 'concluido_em',
    ];

    protected $casts = [
        'valor_previsto' => 'decimal:2',
        'valor_gasto'    => 'decimal:2',
        'prazo'          => 'date',
        'concluido_em'   => 'datetime',
    ];
}
