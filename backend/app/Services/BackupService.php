<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use SQLite3;
use Throwable;

class BackupService
{
    private const REQUIRED_TABLES = ['users', 'categories', 'products', 'documents', 'notifications', 'system_settings', 'migrations'];

    /** @return array{path: string, created_at: string, bytes: int, sha256: string} */
    public function create(?string $directory = null): array
    {
        $database = $this->databasePath();
        $directory ??= storage_path('app/private/backups');
        File::ensureDirectoryExists($directory, 0700, true);
        $path = rtrim($directory, '/').'/scan-save-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4));

        if (! mkdir($path, 0700)) {
            throw new RuntimeException('Cannot create the backup directory.');
        }

        try {
            $this->copyDatabase($database, $path.'/database.sqlite');
            chmod($path.'/database.sqlite', 0600);
            $this->validateDatabase($path.'/database.sqlite');
            $manifest = [
                'format' => 'scan-save-sqlite-v1',
                'created_at' => now()->toIso8601String(),
                'bytes' => filesize($path.'/database.sqlite'),
                'sha256' => hash_file('sha256', $path.'/database.sqlite'),
                'documents' => 'Database BLOBs; included in database.sqlite',
            ];
            File::put($path.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            chmod($path.'/manifest.json', 0600);
            SystemSetting::setValue('last_backup_at', $manifest['created_at']);

            return ['path' => $path, 'created_at' => $manifest['created_at'], 'bytes' => $manifest['bytes'], 'sha256' => $manifest['sha256']];
        } catch (Throwable $exception) {
            File::deleteDirectory($path);
            throw $exception;
        }
    }

    /** @return array{path: string, created_at: string, bytes: int, sha256: string} */
    public function verify(string $path): array
    {
        $path = rtrim($path, '/');
        foreach (['database.sqlite', 'manifest.json'] as $filename) {
            if (! is_file($path.'/'.$filename) || is_link($path.'/'.$filename)) {
                throw new RuntimeException('Backup must contain regular database.sqlite and manifest.json files.');
            }
        }

        $manifest = json_decode(File::get($path.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($manifest) || ($manifest['format'] ?? null) !== 'scan-save-sqlite-v1'
            || ! is_string($manifest['sha256'] ?? null) || ! is_string($manifest['created_at'] ?? null)
            || ($manifest['bytes'] ?? null) !== filesize($path.'/database.sqlite')
            || ! hash_equals($manifest['sha256'], hash_file('sha256', $path.'/database.sqlite'))) {
            throw new RuntimeException('Backup manifest or database checksum is invalid.');
        }

        $this->validateDatabase($path.'/database.sqlite');

        return ['path' => $path, 'created_at' => $manifest['created_at'], 'bytes' => $manifest['bytes'], 'sha256' => $manifest['sha256']];
    }

    public function restore(string $path): string
    {
        if (! app()->isDownForMaintenance()) {
            throw new RuntimeException('Put the application in maintenance mode with php artisan down before restoring.');
        }

        $this->verify($path);
        $database = $this->databasePath();
        if (realpath($path.'/database.sqlite') === realpath($database)) {
            throw new RuntimeException('The backup cannot be the current application database.');
        }

        $safetyBackup = $this->create();
        DB::disconnect();
        $this->copyDatabase($path.'/database.sqlite', $database);
        DB::purge();

        return $safetyBackup['path'];
    }

    private function databasePath(): string
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        if ($connection->getDriverName() !== 'sqlite' || $database === ':memory:' || ! is_file($database)) {
            throw new RuntimeException('These commands support a local SQLite file only. Use the database vendor backup tooling for other drivers.');
        }
        if (! class_exists(SQLite3::class)) {
            throw new RuntimeException('The PHP sqlite3 extension is required for consistent database backups.');
        }
        if ($connection->transactionLevel() !== 0) {
            throw new RuntimeException('A database backup cannot run inside an application transaction.');
        }

        return $database;
    }

    private function copyDatabase(string $source, string $destination): void
    {
        $sourceDatabase = new SQLite3($source, SQLITE3_OPEN_READONLY);
        $sourceDatabase->enableExceptions(true);
        $sourceDatabase->busyTimeout(5000);
        $destinationDatabase = new SQLite3($destination, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $destinationDatabase->enableExceptions(true);
        $destinationDatabase->busyTimeout(5000);

        try {
            if (! $sourceDatabase->backup($destinationDatabase)) {
                throw new RuntimeException('SQLite could not complete the database snapshot.');
            }
        } finally {
            $destinationDatabase->close();
            $sourceDatabase->close();
        }
    }

    private function validateDatabase(string $path): void
    {
        $database = new SQLite3($path, SQLITE3_OPEN_READONLY);
        $database->enableExceptions(true);
        try {
            if ($database->querySingle('PRAGMA integrity_check') !== 'ok') {
                throw new RuntimeException('SQLite integrity check failed.');
            }
            $tables = [];
            $result = $database->query("SELECT name FROM sqlite_master WHERE type = 'table'");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $tables[] = $row['name'];
            }
            if (array_diff(self::REQUIRED_TABLES, $tables)) {
                throw new RuntimeException('This is not a complete Scan & Save database backup.');
            }
        } finally {
            $database->close();
        }
    }
}
