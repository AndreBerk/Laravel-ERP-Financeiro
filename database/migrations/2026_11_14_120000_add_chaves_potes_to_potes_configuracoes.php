<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Palavras-chave (separadas por vírgula) que identificam no extrato as saídas para cada pote. */
    public function up(): void
    {
        Schema::table('potes_configuracoes', function (Blueprint $t) {
            $t->text('chaves_lucro')->nullable();
            $t->text('chaves_reinvestimento')->nullable();
            $t->text('chaves_investimento')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('potes_configuracoes', function (Blueprint $t) {
            $t->dropColumn(['chaves_lucro', 'chaves_reinvestimento', 'chaves_investimento']);
        });
    }
};
