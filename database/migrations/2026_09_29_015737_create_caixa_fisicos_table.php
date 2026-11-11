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
    Schema::create('caixa_fisicos', function (Blueprint $table) {
        $table->id();
        $table->string('descricao'); // Ex: "Troco", "Venda Balcão", "Pagamento de Água"
        $table->enum('tipo', ['entrada', 'saida']); // Define se o dinheiro entrou ou saiu
        $table->decimal('valor', 10, 2); // Campo de valor com 2 casas decimais
        $table->date('data_movimentacao'); // Data em que ocorreu
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caixa_fisicos');
    }
};
