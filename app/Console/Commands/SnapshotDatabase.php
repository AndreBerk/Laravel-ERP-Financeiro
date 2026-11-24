<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

/**
 * Gera um snapshot consistente do banco de desenvolvimento para ser
 * empacotado no .exe. No primeiro uso do app desktop, o snapshot é copiado
 * para o %APPDATA% (ver AppServiceProvider::restaurarSnapshotDesktop).
 *
 * Extensão .db (e não .sqlite) porque o NativePHP exclui database/*.sqlite do pacote.
 */
class SnapshotDatabase extends Command
{
    protected $signature = 'app:snapshot-db';

    protected $description = 'Copia o banco atual para database/snapshot/dados-iniciais.db (incluído no build desktop)';

    public const DESTINO = 'snapshot/dados-iniciais.db';

    public function handle(): int
    {
        $origem = database_path('database.sqlite');
        $destino = database_path(self::DESTINO);

        if (! is_file($origem)) {
            $this->error("Banco não encontrado: {$origem}");

            return self::FAILURE;
        }

        @mkdir(dirname($destino), 0755, true);
        @unlink($destino);

        // VACUUM INTO gera uma cópia íntegra mesmo com o banco em uso/WAL.
        $pdo = new PDO('sqlite:'.$origem);
        $pdo->exec('VACUUM INTO '.$pdo->quote($destino));

        $this->info(sprintf('Snapshot gerado: %s (%.1f KB)', $destino, filesize($destino) / 1024));

        return self::SUCCESS;
    }
}
