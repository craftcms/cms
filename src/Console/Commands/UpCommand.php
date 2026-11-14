<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use Closure;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\MigrateCommand;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\Commands\ApplyCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\CompiledViews;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Console\View\TaskResult;
use Illuminate\Contracts\Console\Isolatable;
use InvalidArgumentException;
use Override;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function CraftCms\Cms\t;
use function Laravel\Prompts\confirm;

/**
 * Runs pending migrations and applies pending project config changes, followed by any additional tasks
 * registered by plugins or the application.
 *
 * ```php
 * public function boot(): void
 * {
 *     // Run an Artisan command
 *     UpCommand::add('Warming caches', 'my-plugin:warm-caches');
 *
 *     // Run a closure, which can return `false` to report a failure
 *     UpCommand::add('Rebuilding search indexes', function (SearchIndexer $indexer, Factory $components) {
 *         $components->warn('Skipped 3 entries without a URL.');
 *
 *         return $indexer->rebuild();
 *     });
 * }
 * ```
 *
 * Output from tasks is shown below their task line. Closures can type-hint
 * `Illuminate\Console\View\Components\Factory` or `Illuminate\Console\OutputStyle` to write it.
 *
 * @since 6.0.0
 */
class UpCommand extends Command implements Isolatable
{
    use CraftCommand;

    /** @var array<string, string|Closure> */
    private static array $tasks = [];

    #[Override]
    protected $signature = 'craft:up {--noBackup : Skip backing up the database.}';

    #[Override]
    protected $description = 'Runs pending migrations and applies pending project config changes.';

    /**
     * Registers a task that runs after Craft’s own tasks, in the order tasks were added.
     *
     * Adding a task with an existing label replaces it.
     *
     * @param  string  $label  The label shown while the task runs.
     * @param  string|Closure  $task  The name of an Artisan command, or a closure, which is resolved through the container
     *                                and can return `false` to report a failure.
     */
    public static function add(string $label, string|Closure $task): void
    {
        if ($label === '') {
            throw new InvalidArgumentException('Up task labels cannot be empty.');
        }

        self::$tasks[$label] = $task;
    }

    public static function flushState(): void
    {
        self::$tasks = [];
    }

    public function handle(Updates $updates, License $license, ProjectConfig $projectConfig): int
    {
        $this->showLicensingIssues($updates, $license);

        try {
            $pendingChanges = $projectConfig->areChangesPending(force: true);
            $writeYamlAutomatically = $projectConfig->writeYamlAutomatically;

            // Craft + plugin migrations
            $res = $this->call(MigrateCommand::class, [
                '--force' => true,
                '--noContent' => true,
                '--noBackup' => $this->option('noBackup'),
            ]);

            if ($res !== self::SUCCESS) {
                $this->components->warn('Aborting remaining tasks.');

                return $res;
            }

            // Save and reset the project config
            $projectConfig->saveModifiedConfigData();
            $projectConfig->reset();

            // Project Config
            if ($pendingChanges) {
                $res = $this->call(ApplyCommand::class);
                if ($res !== self::SUCCESS) {
                    return $res;
                }

                $projectConfig->flush();
                $projectConfig->reset();
            }

            // Content migration
            $res = $this->call(MigrateCommand::class, [
                '--force' => true,
                '--track' => 'content',
            ]);

            if ($res !== self::SUCCESS) {
                return $res;
            }

            $this->call('craft:clear-caches', [
                'keys' => ['compiled-templates'],
            ]);

            $this->clearCompiledViews();
        } catch (Throwable $e) {
            if (! $e instanceof OperationAbortedException) {
                throw $e;
            }

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($writeYamlAutomatically && ! $projectConfig->readOnly) {
            $projectConfig->writeYamlFiles(force: true);
        }

        return $this->runAdditionalTasks();
    }

    /**
     * Runs the registered tasks. A failing task doesn’t stop the ones after it, but fails the command.
     *
     * Each task’s output is buffered and shown below its task line, so it doesn’t break up the line.
     */
    private function runAdditionalTasks(): int
    {
        $result = self::SUCCESS;

        foreach (self::$tasks as $label => $task) {
            $buffer = new BufferedOutput($this->output->getVerbosity(), $this->output->isDecorated());
            $output = new OutputStyle($this->input, $buffer);
            $components = new Factory($output);

            $this->components->task($label, function () use ($task, $output, $components, &$result) {
                try {
                    $succeeded = is_string($task)
                        ? $this->runCommand($task, [], $output) === self::SUCCESS
                        : $this->laravel->call($task, [
                            OutputStyle::class => $output,
                            Factory::class => $components,
                        ]) !== false;
                } catch (Throwable $e) {
                    report($e);
                    $components->error($e->getMessage());
                    $succeeded = false;
                }

                if ($succeeded) {
                    return TaskResult::Success->value;
                }

                $result = self::FAILURE;

                return TaskResult::Failure->value;
            });

            $this->output->write($buffer->fetch(), false, OutputInterface::OUTPUT_RAW);
        }

        return $result;
    }

    /**
     * Clears compiled Blade views, so updated control panel views aren't shadowed by stale compiled copies.
     *
     * The update has already been applied by this point, so a failure is reported rather than failing the command.
     */
    private function clearCompiledViews(): void
    {
        if (CompiledViews::path() === null) {
            return;
        }

        try {
            $this->components->task(t('Clearing:').' '.t('Compiled views'), CompiledViews::clear(...));
        } catch (Throwable $e) {
            report($e);
            $this->components->warn('Could not clear compiled views: '.$e->getMessage().' Run `php artisan view:clear` manually.');
        }
    }

    private function showLicensingIssues(Updates $updates, License $license): bool
    {
        $this->components->task('Updating license info', function () use ($updates) {
            $updates->getUpdates(refresh: true);
        });

        $issues = $license->issues([LicenseKeyStatus::Astray->value]);

        if (empty($issues)) {
            return true;
        }

        $this->components->error('The following licensing issues were detected:');
        $this->components->bulletList(array_map(fn (array $issue) => $issues[1], $issues));

        return confirm('Continue anyway?');
    }
}
