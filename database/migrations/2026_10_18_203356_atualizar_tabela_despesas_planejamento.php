<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('despesas', function (Blueprint $table) {
            // Adiciona os novos campos caso não existam
            if (!Schema::hasColumn('despesas', 'tipo_despesa')) {
                $table->string('tipo_despesa')->default('empresa')->after('id'); // 'pessoal' ou 'empresa'
            }
            if (!Schema::hasColumn('despesas', 'categoria')) {
                $table->string('categoria')->nullable()->after('valor');
            }
            if (!Schema::hasColumn('despesas', 'parcela_atual')) {
                $table->integer('parcela_atual')->nullable();
            }
            if (!Schema::hasColumn('despesas', 'total_parcelas')) {
                $table->integer('total_parcelas')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('despesas', function (Blueprint $table) {
            $table->dropColumn(['tipo_despesa', 'categoria', 'parcela_atual', 'total_parcelas']);
        });
    }
};
