<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Commands;

use Closure;
use Composer\Semver\VersionParser;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\BackupTrait;
use CraftCms\Cms\Database\Commands\MigrateCommand;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Updates\Data\Update;
use CraftCms\Cms\Updates\Data\UpdateRelease;
use CraftCms\Cms\Updates\Enums\UpdateStatus;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Process\Process;
use Throwable;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

final class UpdateCommand extends Command
{
    use BackupTrait;
    use ChecksLicense;
    use Colors;
    use ConfirmableTrait;
    use CraftCommand;
    use FetchesUpdates;

    #[\Override]
    protected $signature = 'craft:update
        {handle?* : The update handle (`all`, `craft`, or a plugin handle). You can pass multiple handles separated by spaces, and you can update to a specific version using the syntax `handle:version`.}
        {--force : Force the operation to run when updates are disallowed.}
        {--with-expired : Whether to update expired licenses. NOTE: This will result in “License purchase required” messages in the control panel on public domains, until the licenses have been renewed.}
        {--minor-only : Whether only minor updates should be applied.}
        {--patch-only : Whether only patch updates should be applied.}
        {--backup : Backup the database before updating.}
        {--no-migrate : Skip running new database migrations after completing the update.}
        {--except=* : Plugin handles to exclude}
    ';

    #[\Override]
    protected $description = 'Updates Craft and/or plugins.';

    private Composer $composer;

    private Plugins $plugins;

    public function handle(Plugins $plugins, Composer $composer, Updates $updates): int
    {
        $this->composer = $composer;
        $this->plugins = $plugins;

        $handles = $this->argument('handle');

        if (empty($handles)) {
            return $this->call(InfoCommand::class);
        }

        if (! $this->confirmToProceed('Updates are disallowed for this environment. Update anyway?')) {
            return self::FAILURE;
        }

        if (! is_null($exitCode = $this->checkLicense())) {
            return $exitCode;
        }

        $requirements = $this->getRequirements($handles);

        if (empty($requirements)) {
            return self::SUCCESS;
        }

        if (! $this->backup($this->option('backup'))) {
            return self::FAILURE;
        }

        if (! $this->performUpdate($requirements)) {
            $this->revertComposerChanges();

            return self::FAILURE;
        }

        if (! $this->migrate()) {
            if ($this->restore()) {
                $this->revertComposerChanges();
            }

            return self::FAILURE;
        }

        $this->components->task(
            'Updating license info',
            fn () => $updates->getUpdates(true),
        );

        $this->components->success('Update complete!');

        return self::SUCCESS;
    }

    private function getRequirements(array $handles): array
    {
        $constraints = [];

        if ($this->option('minor-only') || $this->option('patch-only')) {
            $cmsConstraint = $this->constraint(Cms::VERSION);

            if ($cmsConstraint !== null) {
                $constraints['cms'] = $cmsConstraint;
            }

            foreach ($this->plugins->getAllPlugins() as $plugin) {
                // don't update dev versions
                $version = $plugin->version;

                if (VersionParser::parseStability($version) === 'dev') {
                    continue;
                }

                $pluginConstraint = $this->constraint($version);
                if ($pluginConstraint !== null) {
                    $constraints[$plugin->handle] = $pluginConstraint;
                }
            }
        }

        if ($handles !== ['all']) {
            // Look for any specific versions that were requested
            foreach ($handles as $handle) {
                if (! str_contains((string) $handle, ':')) {
                    continue;
                }
                [$handle, $to] = explode(':', (string) $handle, 2);

                if ($handle === 'craft') {
                    $handle = 'cms';
                }

                $constraints[$handle] = $to;
            }
        }

        $updates = $this->fetchUpdates($constraints);
        $info = [];
        $requirements = [];

        if ($handles === ['all']) {
            $handles = array_merge(['craft'], array_keys($updates->plugins));
        }

        if ($this->option('except')) {
            $handles = array_filter($handles, fn ($handle) => ! in_array(Str::before($handle, ':'), $this->option('except')));
        }

        foreach ($handles as $handle) {
            if (str_contains((string) $handle, ':')) {
                [$handle, $to] = explode(':', (string) $handle, 2);
            } else {
                $to = null;
            }

            if ($handle === 'craft') {
                $version = $to ?? $updates->cms->latest()?->version;

                if (! $version) {
                    continue;
                }

                $this->updateRequirements($requirements, $info, 'craft', Cms::VERSION, $version, 'craftcms/cms', $updates->cms);

                continue;
            }

            $pluginUpdate = $updates->plugins[$handle];
            $version = $to ?? $pluginUpdate->latest()?->version;

            if (! $version) {
                continue;
            }

            try {
                $pluginInfo = $this->plugins->getPluginInfo($handle);
            } catch (InvalidPluginException) {
                continue;
            }

            if (! $pluginInfo['isInstalled']) {
                continue;
            }

            $this->updateRequirements($requirements, $info, $handle, $pluginInfo['version'], $version, $pluginInfo['packageName'], $pluginUpdate);
        }

        if (($total = count($requirements)) === 0) {
            $this->components->success('You’re all up to date!');

            return $requirements;
        }

        $this->newLine();
        $this->components->info($this->green(sprintf(
            ' Performing <fg=green;options=bold>%s</> %s:',
            $total === 1 ? 'one' : $total,
            Str::plural('update', $total),
        )));

        $lines = [];

        foreach ($info as [$handle, $from, $to, $critical, $status, $phpConstraint]) {
            $lines[] = $this->formatLine($handle, $from, new Update(
                status: $status,
                releases: [
                    new UpdateRelease(
                        version: $to,
                        critical: $critical,
                    ),
                ],
                phpConstraint: $phpConstraint,
            ));
        }

        table(
            [Str::padRight('Handle', 10), Str::padRight('From', 10),  Str::padRight('To', 10), Str::padRight('Status', 10)],
            $lines,
        );

        return $requirements;
    }

    private function constraint(string $version): ?string
    {
        if ($this->option('minor-only')) {
            // 1.5.7.0 => ^1.5.7.0
            return "^$version";
        }

        if ($this->option('patch-only')) {
            // 1.5.7.0 => ~1.5.7
            $version = new VersionParser()->normalize($version);
            $parts = explode('.', $version);

            if (count($parts) === 4) {
                return sprintf('~%s.%s.%s', ...array_slice($parts, 0, 3));
            }
        }

        return null;
    }

    private function updateRequirements(array &$requirements, array &$info, string $handle, string $from, ?string $to, string $oldPackageName, Update $update): void
    {
        if ($update->status === UpdateStatus::EXPIRED && ! $this->option('with-expired')) {
            $this->warn("Skipping `$handle`: its license has expired. Run with `--with-expired` to update anyway.");

            return;
        }

        if ($update->phpConstraint && $phpConstraintError = PHP::checkConstraint($update->phpConstraint)) {
            $this->warn("Skipping `$handle`: $phpConstraintError");

            return;
        }

        $to ??= $update->latest()->version ?? $from;

        if ($to === $from) {
            $this->line($this->gray("Skipping `$handle`: it’s already up to date."));

            return;
        }

        $requirements[$update->packageName] = "^$to";
        $info[] = [$handle, $from, $to, $update->hasCritical(), $update->status, $update->phpConstraint];

        // Has the package name changed?
        if ($update->packageName !== $oldPackageName) {
            $requirements[$oldPackageName] = false;
        }
    }

    private function performUpdate(array $requirements): bool
    {
        $output = '';

        try {
            spin(function () use ($requirements, &$output) {
                $this->composer->install($requirements, function ($type, $buffer) use (&$output) {
                    if ($type === Process::OUT) {
                        $output .= $buffer;
                    }
                });
            }, 'Performing update with Composer');
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            $this->line('Output:'.PHP_EOL.PHP_EOL.$output.PHP_EOL.PHP_EOL);

            return false;
        }

        $this->components->twoColumnDetail('Performing update with Composer', $this->green('DONE'));

        return true;
    }

    private function migrate(): bool
    {
        if ($this->option('no-migrate')) {
            $this->line($this->gray('Skipping applying new migrations.'));

            return true;
        }

        $this->call(MigrateCommand::class, [
            '--no-backup' => true,
            '--no-content' => true,
            '--force' => $this->option('force'),
        ]);

        return true;
    }

    private function revertComposerChanges(): void
    {
        // See if we have composer.json and composer.lock backups
        $backupsDir = Path::composerBackups();
        $jsonBackup = join_paths($backupsDir, 'composer.json');
        $lockBackup = join_paths($backupsDir, 'composer.lock');

        if (! is_file($jsonBackup)) {
            $this->error("Can’t revert Composer changes because no composer.json backup exists in $backupsDir.");

            return;
        }

        if (! is_file($lockBackup)) {
            $this->error("Can’t revert Composer changes because no composer.lock backup exists in $backupsDir.");

            return;
        }

        $jsonContents = file_get_contents($jsonBackup);
        $lockContents = file_get_contents($lockBackup);

        // The composer.lock backup could be just a placeholder
        if (! array_key_exists('packages', Json::decode($lockContents))) {
            $this->error('Can’t revert Composer changes because no composer.lock file existed before the update.');

            return;
        }

        if ($this->input->isInteractive() && ! confirm('Revert the Composer changes?')) {
            return;
        }

        $this->components->task(
            'Reverting Composer changes',
            function () use ($lockContents, $jsonContents) {
                File::put($this->composer->getJsonPath(), $jsonContents);
                File::put($this->composer->getLockPath(), $lockContents);

                $this->call('update:composer-install');
            }
        );
    }

    protected function getDefaultConfirmCallback(): Closure
    {
        return fn () => ! Cms::config()->allowUpdates;
    }
}
