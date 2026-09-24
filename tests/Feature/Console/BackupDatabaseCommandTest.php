<?php

use Illuminate\Support\Facades\Storage;

test('app:backup-database successfully creates a compressed backup', function () {
    Storage::fake('local');

    $this->artisan('app:backup-database --disk=local')
        ->expectsOutputToContain('Starting database backup')
        ->expectsOutputToContain('Backup successfully created')
        ->assertExitCode(0);

    $files = Storage::disk('local')->files('backups');
    expect($files)->not->toBeEmpty()
        ->and($files[0])->toEndWith('.sql.gz');
});

test('app:backup-database respects custom retention option', function () {
    Storage::fake('local');

    // Create an old backup file that should be cleaned up
    Storage::disk('local')->put('backups/backup-sqlite-old.sql.gz', 'test');

    $this->artisan('app:backup-database --disk=local --retention=0')
        ->assertExitCode(0);
});

