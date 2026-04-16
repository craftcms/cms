<?php

declare(strict_types=1);

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\Tests\FakeMigrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
    if (! isset($this->databasePath)) {
        return;
    }

    Artisan::clearResolvedInstance('artisan');
    app()->useDatabasePath(base_path('database'));
    File::deleteDirectory($this->databasePath);
});

it('publishes and applies Laravel optional migrations during install', function () {
    $this->databasePath = sys_get_temp_dir().'/craft-optional-migrations-'.uniqid();
    app()->useDatabasePath($this->databasePath);
    File::ensureDirectoryExists($this->databasePath.'/migrations');

    $migrationMap = [
        'make:cache-table' => '2026_01_01_000000_create_cache_table.php',
        'make:queue-table' => '2026_01_01_000001_create_jobs_table.php',
        'make:queue-failed-table' => '2026_01_01_000002_create_failed_jobs_table.php',
        'make:queue-batches-table' => '2026_01_01_000003_create_job_batches_table.php',
        'make:session-table' => '2026_01_01_000004_create_sessions_table.php',
        'make:notifications-table' => '2026_01_01_000005_create_notifications_table.php',
    ];

    Artisan::swap(new class($this->databasePath, $migrationMap)
    {
        public array $calls = [];

        public function __construct(
            private readonly string $databasePath,
            private readonly array $migrationMap,
        ) {}

        public function call(string $command, array $parameters = []): int
        {
            $this->calls[] = [$command, $parameters];

            $filename = $this->migrationMap[$command] ?? null;

            if (! $filename) {
                return 1;
            }

            File::put($this->databasePath.'/migrations/'.$filename, '<?php');

            return 0;
        }
    });

    $migrator = new FakeMigrator;
    $migrator->tracked = 'craft';

    app(LaravelMigrations::class)->install($migrator);

    $artisan = Artisan::getFacadeRoot();

    expect($artisan->calls)->toHaveCount(6)
        ->and(array_column($artisan->calls, 0))->toBe(array_keys($migrationMap))
        ->and($migrator->tracked)->toBe('craft')
        ->and($migrator->runArguments[0])->toBe([
            $this->databasePath.'/migrations/2026_01_01_000000_create_cache_table.php',
            $this->databasePath.'/migrations/2026_01_01_000001_create_jobs_table.php',
            $this->databasePath.'/migrations/2026_01_01_000002_create_failed_jobs_table.php',
            $this->databasePath.'/migrations/2026_01_01_000003_create_job_batches_table.php',
            $this->databasePath.'/migrations/2026_01_01_000005_create_notifications_table.php',
        ])
        ->and($migrator->loggedMigrations)->toContain(['2026_01_01_000004_create_sessions_table', 1])
        ->and($migrator->loggedMigrations)->toContain(['2026_01_01_000000_create_cache_table', 2])
        ->and($migrator->loggedMigrations)->toContain(['2026_01_01_000001_create_jobs_table', 2])
        ->and($migrator->loggedMigrations)->toContain(['2026_01_01_000002_create_failed_jobs_table', 2])
        ->and($migrator->loggedMigrations)->toContain(['2026_01_01_000003_create_job_batches_table', 2])
        ->and($migrator->loggedMigrations)->toContain(['2026_01_01_000005_create_notifications_table', 2]);
});

it('is idempotent when the Laravel optional migrations already exist', function () {
    $this->databasePath = sys_get_temp_dir().'/craft-optional-migrations-'.uniqid();
    app()->useDatabasePath($this->databasePath);
    File::ensureDirectoryExists($this->databasePath.'/migrations');

    foreach ([
        '2026_01_01_000000_create_cache_table.php',
        '2026_01_01_000001_create_jobs_table.php',
        '2026_01_01_000002_create_failed_jobs_table.php',
        '2026_01_01_000003_create_job_batches_table.php',
        '2026_01_01_000004_create_sessions_table.php',
        '2026_01_01_000005_create_notifications_table.php',
    ] as $filename) {
        File::put($this->databasePath.'/migrations/'.$filename, '<?php');
    }

    Artisan::swap(new class
    {
        public array $calls = [];

        public function call(string $command, array $parameters = []): int
        {
            $this->calls[] = [$command, $parameters];

            return 1;
        }
    });

    $migrator = new FakeMigrator;
    $migrator->tracked = 'craft';
    $migrator->loggedMigrations = [
        ['2026_01_01_000000_create_cache_table', 1],
        ['2026_01_01_000001_create_jobs_table', 1],
        ['2026_01_01_000002_create_failed_jobs_table', 1],
        ['2026_01_01_000003_create_job_batches_table', 1],
        ['2026_01_01_000004_create_sessions_table', 1],
        ['2026_01_01_000005_create_notifications_table', 1],
    ];

    app(LaravelMigrations::class)->install($migrator);

    $artisan = Artisan::getFacadeRoot();

    expect($artisan->calls)->toBe([])
        ->and($migrator->tracked)->toBe('craft')
        ->and($migrator->runArguments)->toBe([])
        ->and($migrator->loggedMigrations)->toHaveCount(6);
});

it('marks optional migrations as applied when their tables already exist', function () {
    $this->databasePath = sys_get_temp_dir().'/craft-optional-migrations-'.uniqid();
    app()->useDatabasePath($this->databasePath);
    File::ensureDirectoryExists($this->databasePath.'/migrations');

    File::put($this->databasePath.'/migrations/2026_01_01_000004_create_sessions_table.php', '<?php');

    Schema::dropIfExists('sessions');
    app(LaravelMigrations::class)->ensureSessionsTable();

    $migrator = new FakeMigrator;
    $migrator->tracked = 'craft';

    app(LaravelMigrations::class)->install($migrator);

    expect($migrator->runArguments[0])->not->toContain($this->databasePath.'/migrations/2026_01_01_000004_create_sessions_table.php')
        ->and($migrator->loggedMigrations)->toContain(['2026_01_01_000004_create_sessions_table', 1]);
});
