<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaldoInicial extends Model
{
    use HasFactory;

    protected $table = 'saldos_iniciais';

    // O $guarded vazio desliga a trava de segurança e permite salvar os dados do formulário
    protected $guarded = [];
}
