<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'scan:backup {--directory= : Private backup destination; defaults to storage/app/private/backups}';

    protected $description = 'Create and verify a consistent SQLite snapshot, including binary document contents';

    public function handle(BackupService $backups): int
    {
        try {
            $backup = $backups->create($this->option('directory'));
            $this->info('Verified backup: '.$backup['path']);
            $this->line('Includes the database and every stored document. Copy it to protected offsite storage.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
