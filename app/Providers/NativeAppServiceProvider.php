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
            ->minHeight(640)
            ->center();
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
        ];
    }
}
