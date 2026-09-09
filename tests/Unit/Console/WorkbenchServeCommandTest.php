<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Workbench\App\Console\Commands\ServeCommand;

it('rejects an invalid workbench server port', function () {
    $this->artisan('workbench:serve', ['--port' => 'invalid'])
        ->expectsOutputToContain('The port must be an integer between 1 and 65535.')
        ->assertFailed();
});

it('offers an explicit fresh database option', function () {
    expect(Artisan::all()['workbench:serve']->getDefinition()->hasOption('fresh'))->toBeTrue();
});

it('rejects an invalid workbench URL', function () {
    $this->artisan('workbench:serve', ['--url' => 'ftp://example.com'])
        ->expectsOutputToContain('The URL must be a valid HTTP or HTTPS URL.')
        ->assertFailed();
});

it('does not serve an existing uninitialized workbench database', function () {
    $databasePath = storage_path('framework/testing/'.Str::uuid());
    $originalDatabasePath = app()->databasePath();
    $originalEnvironmentPath = app()->environmentPath();

    File::ensureDirectoryExists($databasePath);
    File::put($databasePath.'/database.sqlite', '');
    File::put($databasePath.'/.env', '');
    app()->useDatabasePath($databasePath);
    app()->useEnvironmentPath($databasePath);

    try {
        $command = Mockery::mock(ServeCommand::class)->makePartial();
        $command->__construct();
        $command->setLaravel(app());
        $command->shouldReceive('call')->with('package:sync-skeleton')->once()->andReturn(Command::SUCCESS);
        $command->shouldReceive('call')->with('package:create-sqlite-db')->once()->andReturn(Command::SUCCESS);
        $command->shouldReceive('call')->with('workbench:build')->once()->andReturn(Command::SUCCESS);
        $command->shouldReceive('call')->with('serve', Mockery::any())->andReturn(Command::SUCCESS);

        $tester = new CommandTester($command);

        expect($tester->execute([
            '--port' => '8123',
            '--url' => 'http://127.0.0.1:8123',
        ]))->toBe(Command::FAILURE)
            ->and($tester->getDisplay())->toContain('Failed to build the Workbench database.');
    } finally {
        app()->useDatabasePath($originalDatabasePath);
        app()->useEnvironmentPath($originalEnvironmentPath);
        File::deleteDirectory($databasePath);
    }
});
