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
    Schema::create('conciliacoes', function (Blueprint $table) {
        $table->id();
        $table->string('origem'); // Vai salvar se é: banco, maquininha ou sistema_vendas
        $table->string('conta_instituicao'); // Ex: Bradesco, Itaú, Cielo, Laranjinha Itaú
        $table->integer('mes_referencia'); // O mês que você selecionou na hora de subir o arquivo
        $table->integer('ano_referencia'); // O ano do arquivo
        $table->date('data_transacao'); // Data exata da linha do extrato
        $table->string('descricao'); // O texto que vem no extrato
        $table->enum('tipo', ['entrada', 'saida']);
        $table->decimal('valor', 12, 2);
        $table->string('categoria')->nullable(); // Se não achar, fica nulo (Sem Categoria)
        $table->boolean('enviado_dre')->default(true); // Já vai direto pro DRE como você pediu
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conciliacaos');
    }
};
