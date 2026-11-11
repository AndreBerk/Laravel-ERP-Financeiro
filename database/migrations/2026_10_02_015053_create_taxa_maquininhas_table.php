<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('taxas_maquininhas', function (Blueprint $table) {
            $table->id();
            $table->string('instituicao'); // ex: itau, cielo
            $table->string('bandeira')->nullable(); // ex: visa, mastercard, elo, pix
            $table->string('modalidade'); // ex: debito, credito_vista, credito_parcelado, pix
            $table->integer('parcela_de')->default(1); // Para faixas de parcelamento
            $table->integer('parcela_ate')->default(1);
            $table->decimal('taxa_percentual', 5, 2); // ex: 1.99
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('taxas_maquininhas');
    }
};
