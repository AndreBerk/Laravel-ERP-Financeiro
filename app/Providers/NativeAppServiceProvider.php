<?php

namespace App\Providers;

use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Abre a janela principal ao iniciar o app.
     * App de janela tradicional: aparece na barra de tarefas, sem tray,
     * sem atalhos globais, e encerra ao fechar a janela (window-all-closed → quit).
     */
    public function boot(): void
    {
        Window::open()
            ->title(config('app.name'))
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(640); // sem x/y o Electron centraliza a janela (Window não tem center())
    }

    /**
     * Diretivas php.ini do runtime embutido.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'upload_max_filesize' => '20M',
            'post_max_size' => '25M',
        ] + $this->opcacheIsolado();
    }

    /**
     * No Windows o OPcache usa memória compartilhada com nome derivado da build do PHP.
     * Outro app NativePHP aberto (mesmo php.exe embutido) já cria essa área, e o nosso
     * php -S aborta com "Opcode handlers are unusable due to ASLR" — a janela nunca abre.
     * cache_id dá um nome próprio à área; file_cache_fallback cobre uma 2ª instância deste app.
     */
    private function opcacheIsolado(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $dir = rtrim(config('nativephp-internal.storage_path') ?: storage_path(), '\\/')
            .DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'opcache';

        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ini = ['opcache.cache_id' => 'sistema-financeiro'];

        if (is_dir($dir)) {
            $ini['opcache.file_cache'] = $dir;
            $ini['opcache.file_cache_fallback'] = '1';
        }

        return $ini;
    }
}
