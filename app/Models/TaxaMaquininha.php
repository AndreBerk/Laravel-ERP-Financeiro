<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxaMaquininha extends Model
{
    protected $table = 'taxas_maquininhas';
    protected $fillable = ['instituicao', 'bandeira', 'modalidade', 'parcela_de', 'parcela_ate', 'taxa_percentual'];
}
