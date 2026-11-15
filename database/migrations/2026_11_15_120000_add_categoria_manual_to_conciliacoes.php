<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Categoria escolhida à mão: a reclassificação automática nunca sobrescreve. */
    public function up(): void
    {
        Schema::table('conciliacoes', function (Blueprint $t) {
            $t->boolean('categoria_manual')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('conciliacoes', function (Blueprint $t) {
            $t->dropColumn('categoria_manual');
        });
    }
};
