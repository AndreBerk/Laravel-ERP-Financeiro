<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('saldos_iniciais', function (Blueprint $table) {
        $table->id();
        $table->string('conta_instituicao'); // ex: bradesco, itau
        $table->integer('mes_referencia');
        $table->integer('ano_referencia');
        $table->decimal('valor', 15, 2)->default(0);
        $table->timestamps();

        // Evita que exista mais de um saldo inicial para o mesmo mês/banco
        $table->unique(['conta_instituicao', 'mes_referencia', 'ano_referencia'], 'saldo_unico_idx');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_inicials');
    }
};
