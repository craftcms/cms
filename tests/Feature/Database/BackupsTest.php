<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Database\Events\AfterCreateBackup;
use CraftCms\Cms\Database\Events\AfterRestoreBackup;
use CraftCms\Cms\Database\Events\BeforeCreateBackup;
use CraftCms\Cms\Database\Events\BeforeRestoreBackup;
use CraftCms\Cms\Database\Exceptions\CommandFailedException;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Cms::config()->backupCommand = null;
    Cms::config()->restoreCommand = null;
    Cms::config()->backupCommandFormat = null;
    Cms::config()->maxBackups = false;

    $this->tempDir = storage_path('runtime/backups-test-'.uniqid('', true));
    File::ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

function backupsTestPhpCommand(string $script, array $args = []): string
{
    $command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($script);

    foreach ($args as $arg) {
        $command .= ' '.$arg;
    }

    return $command;
}

function backupsTestMysqlConnection(array $overrides = []): MySqlConnection
{
    $config = array_merge([
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'craft_test',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
        'prefix' => 'craft_',
    ], $overrides);

    return new MySqlConnection(
        pdo: new PDO('sqlite::memory:'),
        database: $config['database'],
        tablePrefix: $config['prefix'],
        config: $config,
    );
}

function backupsTestPgsqlConnection(array $overrides = []): PostgresConnection
{
    $config = array_merge([
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'craft_test',
        'username' => 'postgres',
        'password' => 'secret',
        'schema' => 'public',
        'prefix' => 'craft_',
    ], $overrides);

    return new PostgresConnection(
        pdo: new PDO('sqlite::memory:'),
        database: $config['database'],
        tablePrefix: $config['prefix'],
        config: $config,
    );
}

it('runs configured backup command and dispatches backup events', function () {
    $beforeEvent = null;
    $afterEvent = null;

    Event::listen(BeforeCreateBackup::class, function (BeforeCreateBackup $event) use (&$beforeEvent) {
        $beforeEvent = $event;
    });

    Event::listen(AfterCreateBackup::class, function (AfterCreateBackup $event) use (&$afterEvent) {
        $afterEvent = $event;
    });

    Cms::config()->backupCommand = backupsTestPhpCommand(
        script: 'file_put_contents($argv[1], "backup-ok");',
        args: ['{file}'],
    );

    $backupPath = $this->tempDir.'/configured-backup.sql';
    app(Backups::class)->backupTo($backupPath);

    expect(is_file($backupPath))->toBeTrue();
    expect(file_get_contents($backupPath))->toBe('backup-ok');
    expect($beforeEvent?->file)->toBe($backupPath);
    expect($beforeEvent?->ignoreTables)->toBeArray();
    expect($afterEvent?->file)->toBe($backupPath);
});

it('returns a generated backup path and writes the backup file', function () {
    Cms::config()->backupCommand = backupsTestPhpCommand(
        script: 'file_put_contents($argv[1], "generated-backup");',
        args: ['{file}'],
    );

    $backupPath = app(Backups::class)->backup();

    expect($backupPath)->toEndWith('.sql');
    expect(is_file($backupPath))->toBeTrue();
    expect(file_get_contents($backupPath))->toBe('generated-backup');

    @unlink($backupPath);
});

it('increments generated backup path names when a collision exists', function () {
    $backups = app(Backups::class);

    $firstPath = $backups->getBackupFilePath();
    file_put_contents($firstPath, 'existing');

    $nextPath = $backups->getBackupFilePath();

    expect($nextPath)->not->toBe($firstPath);
    expect($nextPath)->toEndWith('--1.sql');

    @unlink($firstPath);
});

it('throws when backups are disabled in config', function () {
    Cms::config()->backupCommand = false;

    expect(fn () => app(Backups::class)->backupTo($this->tempDir.'/disabled.sql'))
        ->toThrow(RuntimeException::class, 'backup command is false');
});

it('runs configured restore command and dispatches restore events', function () {
    $beforeEvent = null;
    $afterEvent = null;

    Event::listen(BeforeRestoreBackup::class, function (BeforeRestoreBackup $event) use (&$beforeEvent) {
        $beforeEvent = $event;
    });

    Event::listen(AfterRestoreBackup::class, function (AfterRestoreBackup $event) use (&$afterEvent) {
        $afterEvent = $event;
    });

    $sourcePath = $this->tempDir.'/source.sql';
    $targetPath = $this->tempDir.'/restore-result.txt';
    file_put_contents($sourcePath, 'restore-me');

    Cms::config()->restoreCommand = backupsTestPhpCommand(
        script: 'file_put_contents($argv[2], file_get_contents($argv[1]));',
        args: ['{file}', escapeshellarg($targetPath)],
    );

    app(Backups::class)->restore($sourcePath);

    expect(is_file($targetPath))->toBeTrue();
    expect(file_get_contents($targetPath))->toBe('restore-me');
    expect($beforeEvent?->file)->toBe($sourcePath);
    expect($afterEvent?->file)->toBe($sourcePath);
});

it('throws when restore is disabled in config', function () {
    Cms::config()->restoreCommand = false;

    expect(fn () => app(Backups::class)->restore($this->tempDir.'/disabled.sql'))
        ->toThrow(RuntimeException::class, 'restore command is false');
});

it('throws a command failed exception when backup shell command fails', function () {
    Cms::config()->backupCommand = backupsTestPhpCommand(
        script: 'fwrite(STDERR, "backup-failed"); exit(17);',
    );

    $backupPath = $this->tempDir.'/failure.sql';

    try {
        app(Backups::class)->backupTo($backupPath);
        $this->fail('Expected backupTo() to throw CommandFailedException.');
    } catch (CommandFailedException $e) {
        expect($e->exitCode)->not->toBe(0);
        expect($e->error)->toContain('backup-failed');
    }
});

it('uses mysql default command generation for closure commands and respects event-mutated ignore tables', function () {
    $capturedDefaultCommand = null;
    $connection = backupsTestMysqlConnection();

    Event::listen(BeforeCreateBackup::class, function (BeforeCreateBackup $event) {
        $event->ignoreTables = ['cache'];
    });

    Cms::config()->backupCommand = function (string $command) use (&$capturedDefaultCommand): string {
        $capturedDefaultCommand = $command;

        return backupsTestPhpCommand(
            script: 'file_put_contents($argv[1], "closure-backup");',
            args: ['{file}'],
        );
    };

    $backupPath = $this->tempDir.'/mysql-closure.sql';
    app(Backups::class)->backupTo(
        filePath: $backupPath,
        connection: $connection,
        ignoreTables: ['sessions'],
    );

    expect(is_file($backupPath))->toBeTrue();
    expect(file_get_contents($backupPath))->toBe('closure-backup');
    expect($capturedDefaultCommand)->toContain("--ignore-table='craft_test.craft_cache'");
    expect($capturedDefaultCommand)->not->toContain("--ignore-table='craft_test.craft_sessions'");
});

it('uses postgres backup file extensions based on backup format', function () {
    $connection = backupsTestPgsqlConnection();
    Cms::config()->backupCommandFormat = 'custom';

    $defaultFormatPath = app(Backups::class)->getBackupFilePath(connection: $connection);
    $overrideFormatPath = app(Backups::class)->getBackupFilePath(connection: $connection, backupFormat: 'tar');

    expect($defaultFormatPath)->toEndWith('.dump');
    expect($overrideFormatPath)->toEndWith('.tar');
});

it('uses postgres default restore command variants for closure commands', function () {
    $capturedPlainCommand = null;
    $capturedCustomFormatCommand = null;
    $connection = backupsTestPgsqlConnection();
    $backupPath = $this->tempDir.'/postgres-restore.dump';
    file_put_contents($backupPath, 'placeholder');

    Cms::config()->restoreCommand = function (string $command) use (&$capturedPlainCommand): string {
        $capturedPlainCommand = $command;

        return backupsTestPhpCommand('exit(0);');
    };

    app(Backups::class)->restore(
        filePath: $backupPath,
        connection: $connection,
        restoreFormat: 'plain',
    );

    Cms::config()->restoreCommand = function (string $command) use (&$capturedCustomFormatCommand): string {
        $capturedCustomFormatCommand = $command;

        return backupsTestPhpCommand('exit(0);');
    };

    app(Backups::class)->restore(
        filePath: $backupPath,
        connection: $connection,
        restoreFormat: 'custom',
    );

    expect($capturedPlainCommand)->toMatch('/^(?:\'[^\']*psql(?:\\.exe)?\'|psql(?:\\.exe)?)\\s/');
    expect($capturedCustomFormatCommand)->toMatch('/^(?:\'[^\']*pg_restore(?:\\.exe)?\'|pg_restore(?:\\.exe)?)\\s/');
    expect($capturedCustomFormatCommand)->toContain('--single-transaction');
});
