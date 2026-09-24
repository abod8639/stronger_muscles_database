<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database {--disk=local : The storage disk to store backups} {--retention=7 : Number of days to retain backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database and clean up old backups based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $retentionDays = (int) $this->option('retention');
        $connection = config('database.default');

        $this->info("Starting database backup for connection: [{$connection}] on disk: [{$disk}]...");

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backups/backup-{$connection}-{$timestamp}.sql.gz";

        try {
            $dumpContent = $this->generateDatabaseDump($connection);
            $compressedContent = gzencode($dumpContent, 9);

            if ($compressedContent === false) {
                $this->error('Failed to compress backup content.');
                return self::FAILURE;
            }

            \Illuminate\Support\Facades\Storage::disk($disk)->put($filename, $compressedContent);
            $size = strlen($compressedContent);
            $this->info("Backup successfully created at: [{$filename}] ({$size} bytes)");

            // Cleanup old backups based on retention
            $this->cleanupOldBackups($disk, $retentionDays);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Generate database dump as SQL string.
     */
    protected function generateDatabaseDump(string $connection): string
    {
        $config = config("database.connections.{$connection}");

        if ($connection === 'sqlite') {
            $databasePath = $config['database'] ?? database_path('database.sqlite');
            if (file_exists($databasePath)) {
                return (string) file_get_contents($databasePath);
            }
        }

        // Table dump fallback using standard Schema facade
        $tables = \Illuminate\Support\Facades\Schema::connection($connection)->getTableListing();
        $output = "-- Database Backup for [{$connection}]\n-- Generated at: ".now()->toIso8601String()."\n\n";

        foreach ($tables as $table) {
            $rows = \Illuminate\Support\Facades\DB::connection($connection)->table($table)->get();
            $output .= "-- Table: {$table} (".count($rows)." records)\n";
            $output .= "/* ".json_encode($rows)." */\n\n";
        }

        return $output;
    }

    /**
     * Clean up old backups older than specified retention days.
     */
    protected function cleanupOldBackups(string $disk, int $retentionDays): void
    {
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);
        $files = $storage->files('backups');
        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $deleted = 0;

        foreach ($files as $file) {
            if (str_starts_with(basename($file), 'backup-')) {
                $lastModified = $storage->lastModified($file);
                if ($lastModified < $cutoff) {
                    $storage->delete($file);
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old backup file(s) older than {$retentionDays} days.");
        }
    }
}
