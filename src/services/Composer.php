<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
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
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getComposer()|`Craft::$app->composer`]].
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
            return Json::decode(file_get_contents($this->getJsonPath()));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Installs a given set of packages with Composer.
     *
     * @param array|null $requirements Package name/version pairs, or set to null to run the equivalent of `composer install`
     * @param IOInterface|null $io The IO object that Composer should be instantiated with
     * @throws Throwable if something goes wrong
     */
    public function install(?array $requirements, ?IOInterface $io = null): void
    {
        if ($requirements !== null) {
            $this->backupComposerFiles();
        }

        if ($io === null) {
            $io = new NullIO();
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
            $this->updateRequirements($io, $jsonPath, $requirements);
            $command = array_merge(['update'], array_keys($requirements), ['--with-all-dependencies']);
        } else {
            $command = ['install'];
        }

        try {
            $this->runComposerCommand($io, $jsonPath, $command);
        } catch (Throwable $e) {
            file_put_contents($jsonPath, $backup);
            throw $e;
        }
    }

    /**
     * Uninstalls a given set of packages with Composer.
     *
     * @param string[] $packages Package names
     * @param IOInterface|null $io The IO object that Composer should be instantiated with
     * @throws Throwable if something goes wrong
     */
    public function uninstall(array $packages, ?IOInterface $io = null): void
    {
        $this->backupComposerFiles();

        $packages = array_map('strtolower', $packages);

        if ($io === null) {
            $io = new NullIO();
        }

        // Get composer.json
        $jsonPath = $this->getJsonPath();

        // Create a backup of composer.json in case something goes wrong
        $backup = file_get_contents($jsonPath);

        // Ensure craftcms/plugin-installer is allowed
        $this->ensurePluginInstallerIsAllowed($jsonPath);

        $command = array_merge(['remove'], $packages);

        try {
            $this->runComposerCommand($io, $jsonPath, $command);
        } catch (Throwable $e) {
            file_put_contents($jsonPath, $backup);
            throw $e;
        }
    }

    /**
     * @param IOInterface $io
     * @param string $jsonPath
     * @param string[] $command
     * @throws ProcessFailedException
     */
    private function runComposerCommand(IOInterface $io, string $jsonPath, array $command): void
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
            $process->mustRun(function($type, $buffer) use ($io): void {
                if ($type === Process::ERR) {
                    $io->writeErrorRaw($buffer, false);
                } else {
                    $io->writeRaw($buffer, false);
                }
            });
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
        $json = new JsonFile($jsonPath);
        $config = $json->read();
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

        $this->writeJson($jsonPath, $config);
    }

    /**
     * Ensures composer.json has the craftcms/plugin-installer plugin marked as allowed.
     *
     * @param string $jsonPath
     * @since 3.7.42
     */
    protected function ensurePluginInstallerIsAllowed(string $jsonPath): void
    {
        $json = new JsonFile($jsonPath);
        $config = $json->read();
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

        $this->writeJson($jsonPath, $config);
    }

    /**
     * Updates the composer.json file with new requirements
     *
     * @param IOInterface $io
     * @param string $jsonPath
     * @param array $requirements
     */
    protected function updateRequirements(IOInterface $io, string $jsonPath, array $requirements): void
    {
        $json = new JsonFile($jsonPath);
        $config = $json->read();

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
            ksort($config['require']);
        }

        $this->writeJson($jsonPath, $config);
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
    protected function backupComposerFiles(): void
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

    private function writeJson(string $path, array $value): void
    {
        $json = Json::encode($value, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $indent = $this->detectJsonIndent(file_get_contents($path));
        if ($indent !== '    ') {
            $json = preg_replace_callback('/^ {4,}/m', function(array $match) use ($indent) {
                return strtr($match[0], ['    ' => $indent]);
            }, $json);
        }

        FileHelper::writeToFile($path, $json);
    }

    private function detectJsonIndent(string $json): string
    {
        if (!preg_match('/^\s*\{\s*[\r\n]+([ \t]+)"/', $json, $match)) {
            return '  ';
        }
        return $match[1];
    }
}
