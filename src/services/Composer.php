<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * Composer service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getComposer()|`Craft::$app->getComposer()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Composer extends Component
{
    /**
     * @var string
     */
    public string $composerRepoUrl = 'https://composer.craftcms.com';

    /**
     * @var int The maximum number of composer.json and composer.lock backups to store in storage/composer-backups/
     * @since 3.0.38
     */
    public int $maxBackups = 50;

    /**
     * Returns the path to composer.json.
     *
     * @return string
     * @throws Exception if composer.json can't be located
     */
    public function getJsonPath(): string
    {
        if (defined('CRAFT_COMPOSER_PATH')) {
            if (!is_file(CRAFT_COMPOSER_PATH)) {
                throw new Exception(sprintf('No Composer config found at CRAFT_COMPOSER_PATH (%s).', CRAFT_COMPOSER_PATH));
            }
            return CRAFT_COMPOSER_PATH;
        }

        $jsonPath = Craft::getAlias('@root/composer.json');
        if (!is_file($jsonPath)) {
            throw new Exception("No Composer config found at $jsonPath.");
        }
        return $jsonPath;
    }

    /**
     * Returns the path to composer.lock, if it exists.
     *
     * @return string|null
     * @throws Exception if composer.json can't be located
     */
    public function getLockPath(): ?string
    {
        $jsonPath = $this->getJsonPath();
        // Logic based on \Composer\Factory::createComposer()
        $lockPath = pathinfo($jsonPath, PATHINFO_EXTENSION) === 'json'
            ? substr($jsonPath, 0, -4) . 'lock'
            : $jsonPath . '.lock';
        return file_exists($lockPath) ? $lockPath : null;
    }

    /**
     * Returns the Composer config defined by composer.json.
     *
     * @return array
     * @since 3.5.15
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
     * @param array|null $requirements Package name/version pairs, or set to null to run the equivalent of `composer install`
     * @param callable|null $callback The callback that should be passed to `Process::run()`.
     * @throws Throwable if something goes wrong
     */
    public function install(?array $requirements, ?callable $callback = null): void
    {
        if ($requirements !== null) {
            $this->backupComposerFiles();
        }

        // Get composer.json
        $jsonPath = $this->getJsonPath();

        // Create a backup of composer.json in case something goes wrong
        $backup = file_get_contents($jsonPath);

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
            file_put_contents($jsonPath, $backup);
            throw $e;
        }
    }

    /**
     * Uninstalls a given set of packages with Composer.
     *
     * @param string[] $packages Package names
     * @param callable|null $callback The callback that should be passed to `Process::run()`.
     * @throws Throwable if something goes wrong
     */
    public function uninstall(array $packages, ?callable $callback = null): void
    {
        $this->backupComposerFiles();

        $packages = array_map('strtolower', $packages);

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
            file_put_contents($jsonPath, $backup);
            throw $e;
        }
    }

    /**
     * @param string $jsonPath
     * @param string[] $command
     * @param callable|null $callback
     * @throws ProcessFailedException
     */
    private function runComposerCommand(string $jsonPath, array $command, ?callable $callback): void
    {
        // Copy composer.phar into storage/
        $pharPath = sprintf('%s/composer.phar', Craft::$app->getPath()->getRuntimePath());
        copy(Craft::getAlias('@lib/composer.phar'), $pharPath);

        $command = array_merge([
            App::phpExecutable() ?? 'php',
            $pharPath,
        ], $command, [
            '--working-dir',
            dirname($jsonPath),
            '--no-scripts',
            '--no-ansi',
            '--no-interaction',
        ]);

        $homePath = Craft::$app->getPath()->getRuntimePath() . DIRECTORY_SEPARATOR . 'composer';
        FileHelper::createDirectory($homePath);

        $process = new Process($command, null, [
            'COMPOSER_HOME' => $homePath,
        ]);
        $process->setTimeout(null);

        try {
            $process->mustRun($callback);
        } finally {
            unlink($pharPath);
        }

        // Invalidate opcache
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    /**
     * Ensures composer.craftcms.com is listed as a repository in composer.json
     *
     * @param string $jsonPath
     */
    private function ensurePluginStoreRepo(string $jsonPath): void
    {
        $json = file_get_contents($jsonPath);
        $config = Json::decode($json);
        $craftRepoKey = $this->_findCraftRepo($config);

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
     *
     * @param string $jsonPath
     * @since 3.7.42
     */
    private function ensurePluginInstallerIsAllowed(string $jsonPath): void
    {
        $json = file_get_contents($jsonPath);
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
     *
     * @param string $jsonPath
     * @param array $requirements
     */
    private function updateRequirements(string $jsonPath, array $requirements): void
    {
        $json = file_get_contents($jsonPath);
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
        uksort($packages, fn($a, $b) => strnatcmp($this->prefixPackage($a), $this->prefixPackage($b)));
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
     * @param array $config
     * @return int|string|false The key in `$config['repositories']` referencing composer.craftcms.com
     */
    private function _findCraftRepo(array $config): int|string|false
    {
        if (!isset($config['repositories'])) {
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
        $jsonBackupPath = $backupsDir . DIRECTORY_SEPARATOR . 'composer.json';
        $lockBackupPath = $backupsDir . DIRECTORY_SEPARATOR . 'composer.lock';
        FileHelper::cycle($jsonBackupPath, $this->maxBackups);
        FileHelper::cycle($lockBackupPath, $this->maxBackups);

        copy($this->getJsonPath(), $jsonBackupPath);

        $lockPath = $this->getLockPath();
        if (is_file($lockPath)) {
            copy($lockPath, $lockBackupPath);
        } else {
            FileHelper::writeToFile($lockBackupPath, Json::encode([
                '_readme' => [
                    'No composer.lock file existed at the time of backup.',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
}
