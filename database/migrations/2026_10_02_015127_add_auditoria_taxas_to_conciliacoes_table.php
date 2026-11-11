<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('conciliacoes', function (Blueprint $table) {
            $table->decimal('valor_bruto', 10, 2)->nullable()->after('valor');
            $table->decimal('taxa_cobrada_percentual', 5, 2)->nullable()->after('valor_bruto');
            $table->decimal('taxa_acordada_percentual', 5, 2)->nullable()->after('taxa_cobrada_percentual');
            $table->boolean('divergencia_taxa')->default(false)->after('taxa_acordada_percentual');
        });
    }

    public function down()
    {
        Schema::table('conciliacoes', function (Blueprint $table) {
            $table->dropColumn(['valor_bruto', 'taxa_cobrada_percentual', 'taxa_acordada_percentual', 'divergencia_taxa']);
        });
    }
};
