<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('potes_configuracoes', function (Blueprint $t) {
            $t->id();
            $t->decimal('pct_operacao', 5, 2)->default(60.00);
            $t->decimal('pct_reinvestimento', 5, 2)->default(15.00);
            $t->decimal('pct_lucro', 5, 2)->default(25.00);
            $t->decimal('pct_investimento', 5, 2)->default(0.00);
            $t->string('reserva_tipo', 12)->default('valor');
            $t->decimal('reserva_valor', 15, 2)->default(0.00);
            $t->decimal('taxa_cdi', 5, 2)->default(0.00);
            $t->decimal('pct_do_cdi', 6, 2)->default(100.00);
            $t->decimal('cota_socio1', 5, 2)->default(40.00);
            $t->decimal('cota_socio2', 5, 2)->default(30.00);
            $t->decimal('cota_socio3', 5, 2)->default(30.00);
            $t->string('nome_socio1', 100)->nullable();
            $t->string('nome_socio2', 100)->nullable();
            $t->string('nome_socio3', 100)->nullable();
            $t->timestamps();
        });

        Schema::create('potes_faturamentos', function (Blueprint $t) {
            $t->id();
            $t->integer('mes');
            $t->integer('ano');
            $t->decimal('faturamento_informado', 15, 2);
            $t->decimal('valor_reserva', 15, 2)->default(0.00);
            $t->decimal('valor_operacao', 15, 2);
            $t->decimal('valor_reinvestimento', 15, 2);
            $t->decimal('valor_lucro', 15, 2);
            $t->decimal('valor_investimento', 15, 2)->default(0.00);
            $t->timestamps();
            $t->unique(['mes', 'ano']);
        });

        Schema::create('potes_despesas', function (Blueprint $t) {
            $t->id();
            $t->integer('mes');
            $t->integer('ano');
            $t->string('descricao');
            $t->decimal('valor', 15, 2);
            $t->date('data_despesa');
            $t->string('recorrencia', 50);
            $t->integer('parcela_atual')->nullable();
            $t->integer('total_parcelas')->nullable();
            $t->timestamps();
        });

        Schema::create('potes_saques', function (Blueprint $t) {
            $t->id();
            $t->string('tipo_pote', 50);
            $t->decimal('valor', 15, 2);
            $t->string('descricao_beneficiario')->nullable();
            $t->date('data_saque');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('potes_saques');
        Schema::dropIfExists('potes_despesas');
        Schema::dropIfExists('potes_faturamentos');
        Schema::dropIfExists('potes_configuracoes');
    }
};
