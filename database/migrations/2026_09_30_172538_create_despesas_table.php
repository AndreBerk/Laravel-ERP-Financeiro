<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('despesas', function (Blueprint $table) {
        $table->id();
        $table->string('nome'); // Ex: Aluguel, Internet
        $table->decimal('valor', 10, 2); // Valor da despesa
        $table->integer('dia_vencimento'); // Dia do mês que costuma vencer
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
