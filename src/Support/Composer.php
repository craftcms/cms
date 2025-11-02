<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Aliases\Aliases;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * @internal
 */
#[Singleton]
final class Composer
{
    public function __construct(
        public string $composerRepoUrl = 'https://composer.craftcms.com',
        /** @var int The maximum number of composer.json and composer.lock backups to store in storage/composer-backups/ */
        public int $maxBackups = 50,
    ) {}

    /**
     * Returns the path to composer.json.
     *
     * @throws FileNotFoundException if composer.json can't be located
     */
    public function getJsonPath(): string
    {
        if (defined('CRAFT_COMPOSER_PATH')) {
            throw_unless(File::exists(CRAFT_COMPOSER_PATH), FileNotFoundException::class, sprintf('No Composer config found at CRAFT_COMPOSER_PATH (%s).', CRAFT_COMPOSER_PATH));

            return CRAFT_COMPOSER_PATH;
        }

        $jsonPath = Aliases::get('@root/composer.json');

        throw_unless(File::exists($jsonPath), FileNotFoundException::class, "No Composer config found at $jsonPath.");

        return $jsonPath;
    }

    /**
     * Returns the path to composer.lock, if it exists.
     *
     * @throws FileNotFoundException if composer.json can't be located
     */
    public function getLockPath(): ?string
    {
        $jsonPath = $this->getJsonPath();

        // Logic based on \Composer\Factory::createComposer()
        $lockPath = File::extension($jsonPath) === 'json'
            ? Str::beforeLast($jsonPath, '.json').'.lock'
            : $jsonPath.'.lock';

        return File::exists($lockPath) ? $lockPath : null;
    }

    /**
     * Returns the Composer config defined by composer.json.
     */
    public function getConfig(): array
    {
        try {
            return Json::decodeFromFile($this->getJsonPath());
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Installs a given set of packages with Composer.
     *
     * @param  array|null  $requirements  Package name/version pairs, or set to null to run the equivalent of `composer install`
     * @param  callable|null  $callback  The callback that should be passed to `Process::run()`.
     *
     * @throws Throwable if something goes wrong
     */
    public function install(?array $requirements = null, ?callable $callback = null): void
    {
        if ($requirements !== null) {
            $this->backupComposerFiles();
        }

        // Get composer.json
        $jsonPath = $this->getJsonPath();

        // Create a backup of composer.json in case something goes wrong
        $backup = File::get($jsonPath);

        // Ensure composer.craftcms.com is listed as a repository
        $this->ensurePluginStoreRepo($jsonPath);

        // Ensure craftcms/plugin-installer is allowed
        $this->ensurePluginInstallerIsAllowed($jsonPath);

        if ($requirements !== null) {
            $this->updateRequirements($jsonPath, $requirements);
            $command = array_merge(['update'], array_keys($requirements), ['--with-all-dependencies']);
        } else {
            $command = ['install'];
        }

        try {
            $this->runComposerCommand($jsonPath, $command, $callback);
        } catch (Throwable $e) {
            File::put($jsonPath, $backup);

            throw $e;
        }
    }

    /**
     * Uninstalls a given set of packages with Composer.
     *
     * @param  string[]  $packages  Package names
     * @param  callable|null  $callback  The callback that should be passed to `Process::run()`.
     *
     * @throws Throwable if something goes wrong
     */
    public function uninstall(array $packages, ?callable $callback = null): void
    {
        $this->backupComposerFiles();

        $packages = array_map(strtolower(...), $packages);

        // Get composer.json
        $jsonPath = $this->getJsonPath();

        // Create a backup of composer.json in case something goes wrong
        $backup = file_get_contents($jsonPath);

        // Ensure craftcms/plugin-installer is allowed
        $this->ensurePluginInstallerIsAllowed($jsonPath);

        $command = array_merge(['remove'], $packages);

        try {
            $this->runComposerCommand($jsonPath, $command, $callback);
        } catch (Throwable $e) {
            File::put($jsonPath, $backup);
            throw $e;
        }
    }

    /**
     * @param  string[]  $command
     *
     * @throws ProcessFailedException
     */
    private function runComposerCommand(string $jsonPath, array $command, ?callable $callback): void
    {
        $composerPath = new ExecutableFinder()->find('composer');
        $pharPath = '';

        if (! $composerPath) {
            $runtimePath = Craft::$app->getPath()->getRuntimePath();

            // Copy composer.phar into storage/
            $pharPath = sprintf('%s/composer.phar', $runtimePath);
            copy(Aliases::get('@lib/composer.phar'), $pharPath);

            $homePath = $runtimePath.DIRECTORY_SEPARATOR.'composer';
            File::ensureDirectoryExists($homePath);
        }

        $command = array_merge([
            PHP::executable() ?? 'php',
            $composerPath ?? $pharPath,
        ], $command, [
            '--working-dir',
            dirname($jsonPath),
            '--no-scripts',
            '--no-ansi',
            '--no-interaction',
        ]);

        try {
            new Process($command, null, array_filter([
                'COMPOSER_HOME' => $homePath ?? null,
            ]))
                ->setTimeout(null)
                ->mustRun($callback);
        } finally {
            if ($pharPath && File::exists($pharPath)) {
                File::delete($pharPath);
            }
        }

        // Invalidate opcache
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    /**
     * Ensures composer.craftcms.com is listed as a repository in composer.json
     */
    private function ensurePluginStoreRepo(string $jsonPath): void
    {
        $json = File::get($jsonPath);
        $config = Json::decode($json);
        $craftRepoKey = $this->findCraftRepo($config);

        // If it already exists and is marked as non-canonical, we're done
        if (
            $craftRepoKey !== false &&
            // Make sure it's not canonical
            ($config['repositories'][$craftRepoKey]['canonical'] ?? null) === false
        ) {
            return;
        }

        $repoConfig = [
            'type' => 'composer',
            'url' => $this->composerRepoUrl,
            'canonical' => false,
        ];

        if ($craftRepoKey !== false) {
            $config['repositories'][$craftRepoKey] = $repoConfig;
        } else {
            $config['repositories'][] = $repoConfig;
        }

        Json::encodeToFile($jsonPath, $config);
    }

    /**
     * Ensures composer.json has the craftcms/plugin-installer plugin marked as allowed.
     */
    private function ensurePluginInstallerIsAllowed(string $jsonPath): void
    {
        $json = File::get($jsonPath);
        $config = Json::decode($json);
        $allowPlugins = $config['config']['allow-plugins'] ?? [];

        if ($allowPlugins === true) {
            return;
        }

        $plugins = [
            'craftcms/plugin-installer',
            'yiisoft/yii2-composer',
        ];

        // See if everything is already in place
        $hasAllPlugins = true;
        foreach ($plugins as $plugin) {
            if (($allowPlugins[$plugin] ?? false) !== true) {
                $hasAllPlugins = false;
                break;
            }
        }

        if ($hasAllPlugins) {
            return;
        }

        foreach ($plugins as $plugin) {
            $config['config']['allow-plugins'][$plugin] = true;
        }

        Json::encodeToFile($jsonPath, $config);
    }

    /**
     * Updates the composer.json file with new requirements
     */
    private function updateRequirements(string $jsonPath, array $requirements): void
    {
        $json = File::get($jsonPath);
        $config = Json::decode($json);

        foreach ($requirements as $package => $constraint) {
            if ($constraint === false) {
                unset($config['require'][$package]);
            } else {
                $config['require'][$package] = $constraint;
            }

            // Also remove the package from require-dev
            unset($config['require-dev'][$package]);
        }

        if ($config['config']['sort-packages'] ?? false) {
            $this->sortPackages($config['require']);
        }

        Json::encodeToFile($jsonPath, $config);
    }

    public function sortPackages(&$packages): void
    {
        // Adapted from JsonManipulator::sortPackages()
        uksort($packages, fn ($a, $b) => strnatcmp($this->prefixPackage($a), $this->prefixPackage($b)));
    }

    private function prefixPackage(string $package): string
    {
        if (preg_match('/^(?:php(?:-64bit|-ipv6|-zts|-debug)?|hhvm|(?:ext|lib)-[a-z0-9](?:[_.-]?[a-z0-9]+)*|composer(?:-(?:plugin|runtime)-api)?)$/iD', $package)) {
            $lower = strtolower($package);
            if (str_starts_with($lower, 'php')) {
                $group = '0';
            } elseif (str_starts_with($lower, 'hhvm')) {
                $group = '1';
            } elseif (str_starts_with($lower, 'ext')) {
                $group = '2';
            } elseif (str_starts_with($lower, 'lib')) {
                $group = '3';
            } elseif (preg_match('/^\D/', $lower)) {
                $group = '4';
            }
        }

        return sprintf('%s-%s', $group ?? '5', $package);
    }

    /**
     * @return int|string|false The key in `$config['repositories']` referencing composer.craftcms.com
     */
    private function findCraftRepo(array $config): int|string|false
    {
        if (! isset($config['repositories'])) {
            return false;
        }

        foreach ($config['repositories'] as $key => $repository) {
            if (isset($repository['url']) && rtrim($repository['url'], '/') === $this->composerRepoUrl) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Backs up the composer.json and composer.lock files to `storage/composer-backups/`
     */
    private function backupComposerFiles(): void
    {
        $backupsDir = Craft::$app->getPath()->getComposerBackupsPath();
        $jsonBackupPath = $backupsDir.DIRECTORY_SEPARATOR.'composer.json';
        $lockBackupPath = $backupsDir.DIRECTORY_SEPARATOR.'composer.lock';
        FileHelper::cycle($jsonBackupPath, $this->maxBackups);
        FileHelper::cycle($lockBackupPath, $this->maxBackups);

        File::copy($this->getJsonPath(), $jsonBackupPath);

        $lockPath = $this->getLockPath();
        if (File::exists($lockPath)) {
            File::copy($lockPath, $lockBackupPath);
        } else {
            FileHelper::writeToFile($lockBackupPath, Json::encode([
                '_readme' => [
                    'No composer.lock file existed at the time of backup.',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
}
