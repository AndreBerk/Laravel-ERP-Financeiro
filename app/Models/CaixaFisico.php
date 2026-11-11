<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaixaFisico extends Model
{
    use HasFactory;

    // Libera esses campos para serem salvos no banco
    protected $fillable = ['descricao', 'tipo', 'valor', 'data_movimentacao'];
}
