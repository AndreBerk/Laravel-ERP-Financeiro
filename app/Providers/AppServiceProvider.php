<?php

namespace App\Providers;

use App\Console\Commands\SnapshotDatabase;
use App\Models\TaxaMaquininha;
use App\Models\User;
use Database\Seeders\TaxasMaquininhasSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->restaurarSnapshotDesktop();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->garantirDadosMinimosDesktop();
    }

    /**
     * Primeiro uso do .exe: o Electron cria um database.sqlite vazio no %APPDATA%
     * e depois roda as migrations. Antes de qualquer conexão, substituímos esse
     * arquivo vazio pelo snapshot empacotado (dados de desenvolvimento).
     * Nunca sobrescreve um banco que já tenha conteúdo.
     */
    private function restaurarSnapshotDesktop(): void
    {
        if (! config('nativephp-internal.running')) {
            return;
        }

        $destino = config('nativephp-internal.database_path');
        $snapshot = database_path(SnapshotDatabase::DESTINO);

        clearstatcache();

        if ($destino && is_file($snapshot) && (! is_file($destino) || filesize($destino) === 0)) {
            @mkdir(dirname($destino), 0755, true);
            copy($snapshot, $destino);
        }
    }

    /**
     * Trava de segurança no app desktop: se não houver usuário ou taxas, recria.
     */
    private function garantirDadosMinimosDesktop(): void
    {
        if (! config('nativephp-internal.running')) {
            return;
        }

        try {
            if (Schema::hasTable('users') && User::query()->doesntExist()) {
                User::create([
                    'name' => config('app.admin.name'),
                    'email' => config('app.admin.email'),
                    'password' => config('app.admin.password'),
                ]);
            }

            if (Schema::hasTable('taxas_maquininhas') && TaxaMaquininha::query()->doesntExist()) {
                app(TaxasMaquininhasSeeder::class)->run();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
