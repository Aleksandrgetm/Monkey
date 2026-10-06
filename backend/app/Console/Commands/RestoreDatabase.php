<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class RestoreDatabase extends Command
{
    protected $signature = 'scan:restore {backup : Directory containing database.sqlite and manifest.json} {--verify : Validate without changing any application data} {--force : Explicitly authorize replacing the current database while in maintenance mode}';

    protected $description = 'Verify or restore a Scan & Save SQLite backup; restoring creates a safety snapshot first';

    public function handle(BackupService $backups): int
    {
        try {
            if ($this->option('verify')) {
                $backup = $backups->verify($this->argument('backup'));
                $this->info('Backup verified: '.$backup['created_at'].'; '.$backup['bytes'].' bytes.');

                return self::SUCCESS;
            }
            if (! $this->option('force')) {
                $this->error('Restore replaces the current database. Inspect with --verify; use --force only after php artisan down and stopping workers/scheduler.');

                return self::FAILURE;
            }
            $safetyPath = $backups->restore($this->argument('backup'));
            $this->info('Database and documents restored. Pre-restore safety snapshot: '.$safetyPath);
            $this->line('Run migrations and application checks before php artisan up.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
