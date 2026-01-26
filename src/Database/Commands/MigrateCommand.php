<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Events\RegisterMigrators;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\UpCommand;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Throwable;

use function Laravel\Prompts\confirm;

final class MigrateCommand extends Command implements Isolatable
{
    use BackupTrait;
    use Colors;
    use ConfirmableTrait;
    use CraftCommand;
    use DrawsBoxes;

    protected $signature = 'craft:migrate:all
        {--force : Force the operation to run when in production}
        {--pretend : Dump the SQL queries that would be run}
        {--step : Force the migrations to be run so they can be rolled back individually}
        {--no-backup : Skip backing up the database.}
        {--noBackup : Skip backing up the database. [DEPRECATED]}
        {--no-content : Exclude pending content migrations.}
        {--noContent : Exclude pending content migrations. [DEPRECATED]}
        {--track= : The migration track to work with (e.g. `craft`, `content`, `plugin:commerce`, etc.)}
        {--graceful : Return a successful exit code even if an error occurs}';

    protected $description = 'Run the database migrations';

    protected $aliases = ['migrate/all', 'migrate:up', 'migrate/up'];

    private Updates $updates;

    private Plugins $plugins;

    /**
     * @var array<string, Migrator>
     */
    private array $migrators = [];

    public function handle(Updates $updates, Plugins $plugins): int
    {
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        $this->updates = $updates;
        $this->plugins = $plugins;

        try {
            $this->runMigrations();
        } catch (Throwable $e) {
            if ($this->option('graceful')) {
                $this->components->warn($e->getMessage());

                return self::SUCCESS;
            }

            throw $e;
        }

        return self::SUCCESS;
    }

    private function getMigrator(string $track): Migrator
    {
        if (str_starts_with($track, 'plugin')) {
            $handle = substr($track, 7);

            return $this->plugins->getPlugin($handle)
                ->getMigrator()
                ->setOutput($this->output);
        }

        return ($this->migrators[$track] ??= app(Migrator::class)->track($track))
            ->setOutput($this->output);
    }

    private function runMigrations(): void
    {
        $this->prepareDatabase();

        $migrationsByTrack = [];
        $plugins = [];

        $message = match (true) {
            (bool) $this->option('track') => "Checking for pending {$this->option('track')} migrations",
            (bool) $this->option('noContent') => 'Checking for pending Craft and plugin migrations',
            default => 'Checking for pending migrations',
        };

        $this->components->task(
            $message,
            function () use (&$migrationsByTrack, &$plugins) {
                $this->gatherMigrationsByTrack($migrationsByTrack, $plugins);
            },
        );

        if (empty($migrationsByTrack)) {
            $this->components->success('No new migrations found. Your system is up to date.');

            return;
        }

        $total = 0;
        foreach ($migrationsByTrack as $track => $migrations) {
            $n = count($migrations);

            $which = match (true) {
                $track === 'craft' => 'Craft',
                $track === 'content' => 'content',
                str_starts_with((string) $track, 'plugin') => $plugins[substr((string) $track, 7)]->name,
                default => $track,
            };

            $this->box(
                $this->cyan("Total $n new $which ".Str::plural('migration', $n).' to be applied:'),
                collect($migrations)
                    ->map(fn (string $migration) => $this->getMigrator($track)->getMigrationName($migration))
                    ->join("\n")
            );
            $this->newLine();

            $total += $n;
        }

        if ($this->input->isInteractive() && ! confirm('Apply the above '.Str::plural('migration', $total).'?', true)) {
            return;
        }

        $this->call(DownCommand::class);

        $noBackup = $this->option('no-backup') ?? $this->option('noBackup');
        if (! $noBackup && ! $this->option('pretend') && ! $this->backup()) {
            $this->call(UpCommand::class);

            return;
        }

        foreach ($migrationsByTrack as $track => $migrations) {
            $this->getMigrator($track)->run(options: [
                'pretend' => $this->option('pretend'),
                'step' => $this->option('step'),
            ]);

            // Update version info
            if ($track === 'craft') {
                $this->updates->updateCraftVersionInfo();
            } elseif (str_starts_with((string) $track, 'plugin')) {
                $this->plugins->updatePluginVersionInfo($plugins[substr((string) $track, 7)]);
            }
        }

        $this->call(UpCommand::class);
    }

    private function gatherMigrationsByTrack(array &$migrationsByTrack, array &$plugins): void
    {
        if (! $this->option('track') || $this->option('track') === 'craft') {
            $craftMigrations = $this->getMigrator('craft')->getPendingMigrations();
            if (! empty($craftMigrations) || $this->updates->isCraftUpdatePending()) {
                $migrationsByTrack['craft'] = $craftMigrations;
            }
        }

        $plugins = $this->plugins->getAllPlugins();
        foreach ($plugins as $plugin) {
            if ($this->option('track') && $this->option('track') !== "plugin:$plugin->handle") {
                continue;
            }

            $pluginMigrations = $plugin->getMigrator()->getPendingMigrations();
            if (! empty($pluginMigrations) || $this->plugins->isPluginUpdatePending($plugin)) {
                $migrationsByTrack["plugin:$plugin->handle"] = $pluginMigrations;
            }
        }

        $noContent = $this->option('no-content') ?? $this->option('noContent');
        if (! $noContent && (! $this->option('track') || $this->option('track') === 'content')) {
            $contentMigrations = $this->getMigrator('content')->getPendingMigrations();
            if (! empty($contentMigrations)) {
                $migrationsByTrack['content'] = $contentMigrations;
            }
        }

        event($event = new RegisterMigrators);

        foreach ($event->migrators as $migrator) {
            if (! $migrator instanceof Migrator) {
                $this->components->warn($migrator::class.' is not an instance of '.Migrator::class);

                continue;
            }

            if (! $track = $migrator->getTrack()) {
                $this->components->warn('A migrator was registered without a track.');

                continue;
            }

            if ($this->option('track') && $this->option('track') !== $track) {
                continue;
            }

            $this->migrators[$track] = $migrator;

            if (! empty($migrations = $migrator->getPendingMigrations())) {
                $migrationsByTrack[$track] = $migrations;
            }
        }
    }

    /**
     * Prepare the migration database for running.
     */
    private function prepareDatabase(): void
    {
        if ($this->getMigrator('craft')->repositoryExists()) {
            return;
        }

        $this->components->info('Preparing database.');

        $this->components->task('Creating migration table', fn () => $this->callSilent('migrate:install') === 0);

        $this->newLine();
    }
}
