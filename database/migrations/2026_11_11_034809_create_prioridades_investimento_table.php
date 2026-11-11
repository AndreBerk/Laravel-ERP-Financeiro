<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prioridades_investimento', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->string('categoria', 60)->nullable();
            $table->string('prioridade', 20)->default('importante'); // urgente | importante | pode_esperar
            $table->string('status', 20)->default('pendente');       // pendente | em_andamento | concluido
            $table->decimal('valor_previsto', 12, 2)->default(0);
            $table->decimal('valor_gasto', 12, 2)->nullable();
            $table->date('prazo')->nullable();
            $table->string('observacao', 500)->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamps();

            $table->index(['status', 'prioridade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prioridades_investimento');
    }
};
