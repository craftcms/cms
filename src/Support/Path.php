<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

use function Illuminate\Filesystem\join_paths;

#[Singleton]
class Path
{
    public function __construct(
        private readonly Application $app,
        private readonly GeneralConfig $generalConfig,
        private readonly ProjectConfig $projectConfig,
        private readonly License $license,
    ) {}

    private ?string $configPath = null;

    private ?string $storagePath = null;

    private ?string $testsPath = null;

    private ?string $siteTranslationsPath = null;

    private ?string $vendorPath = null;

    public function config(string $path = ''): string
    {
        $this->configPath ??= File::normalizePath($this->app->configPath('craft'));

        return $this->path($this->configPath, $path);
    }

    public function projectConfigFile(): string
    {
        return $this->projectConfig(ProjectConfig::CONFIG_FILENAME, create: false);
    }

    public function projectConfig(string $path = '', bool $create = true): string
    {
        return $this->directory(
            $this->config($this->projectConfig->folderName),
            $path,
            $create,
        );
    }

    public function storage(string $path = '', bool $create = true): string
    {
        $this->storagePath ??= File::normalizePath($this->app->storagePath());

        return $this->directory($this->storagePath, $path, $create);
    }

    public function tests(string $path = ''): string
    {
        $this->testsPath ??= File::normalizePath($this->app->basePath('tests'));

        return $this->path($this->testsPath, $path);
    }

    public function composerBackups(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->storage(create: $create), 'composer-backups', $path, $create, true);
    }

    public function configBackup(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->storage(create: $create), 'config-backups', $path, $create, true);
    }

    public function configDelta(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->storage(create: $create), 'config-deltas', $path, $create, true);
    }

    public function vendor(string $path = ''): string
    {
        $this->vendorPath ??= File::normalizePath($this->app->basePath('vendor'));

        return $this->path($this->vendorPath, $path);
    }

    public function runtime(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->storage(create: $create), 'runtime', $path, $create, true);
    }

    public function dbBackup(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->storage(create: $create), 'backups', $path, $create);
    }

    public function temp(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->runtime(create: $create), 'temp', $path, $create);
    }

    public function assets(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->runtime(create: $create), 'assets', $path, $create);
    }

    public function tempAssetUploads(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->assets(create: $create), 'tempuploads', $path, $create);
    }

    public function assetSources(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->assets(create: $create), 'sources', $path, $create);
    }

    public function imageEditorSources(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->assets(create: $create), 'imageeditor', $path, $create);
    }

    public function assetsIcons(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->assets(create: $create), 'icons', $path, $create);
    }

    public function imageTransforms(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->assets(create: $create), 'imagetransforms', $path, $create);
    }

    public function pluginIcons(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->runtime(create: $create), 'pluginicons', $path, $create);
    }

    public function logs(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->storage(create: $create), 'logs', $path, $create);
    }

    public function package(string $path = ''): string
    {
        return $this->path(File::normalizePath(__DIR__.'/../..'), $path);
    }

    public function resources(string $path = ''): string
    {
        return $this->path($this->package('resources'), $path);
    }

    public function cpTranslations(string $path = ''): string
    {
        return $this->path($this->resources('translations'), $path);
    }

    public function siteTranslations(string $path = ''): string
    {
        $this->siteTranslationsPath ??= File::normalizePath($this->app->langPath());

        return $this->path($this->siteTranslationsPath, $path);
    }

    public function cpTemplates(string $path = ''): string
    {
        return $this->path($this->resources('templates'), $path);
    }

    public function siteTemplates(string $path = ''): string
    {
        $templatesPath = config('view.paths.0');

        if (! is_string($templatesPath) || ! is_dir($templatesPath)) {
            $templatesPath = $this->app->basePath('templates');
        }

        return $this->path(File::normalizePath($templatesPath), $path);
    }

    public function compiledClasses(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->runtime(create: $create), 'compiled_classes', $path, $create);
    }

    public function compiledTemplates(string $path = '', bool $create = true): string
    {
        if ($this->generalConfig->compiledTemplatesPath !== null) {
            $compiledTemplatesPath = Env::parse($this->generalConfig->compiledTemplatesPath);

            if ($compiledTemplatesPath !== null && $compiledTemplatesPath !== '') {
                if (str_starts_with($compiledTemplatesPath, '@')) {
                    throw new InvalidArgumentException('Path aliases require craftcms/yii2-adapter. Use an absolute path instead.');
                }

                return $this->directory(
                    File::normalizePath($compiledTemplatesPath),
                    $path,
                    $create,
                );
            }
        }

        return $this->subdirectory($this->runtime(create: $create), 'compiled_templates', $path, $create);
    }

    public function sessions(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->runtime(create: $create), 'sessions', $path, $create);
    }

    public function cache(string $path = '', bool $create = true): string
    {
        return $this->subdirectory($this->runtime(create: $create), 'cache', $path, $create);
    }

    public function licenseKey(): string
    {
        return $this->license->keyPath();
    }

    /** @return string[] */
    public function system(): array
    {
        return [
            $this->app->basePath('migrations'),
            $this->composerBackups(create: false),
            $this->configBackup(create: false),
            $this->configDelta(create: false),
            $this->config(),
            $this->dbBackup(create: false),
            $this->logs(create: false),
            $this->runtime(create: false),
            $this->siteTemplates(),
            $this->siteTranslations(),
            $this->tests(),
            $this->vendor(),
        ];
    }

    /**
     * Ensures that a relative path never goes deeper than its root directory.
     */
    public static function ensurePathIsContained(string $path): bool
    {
        // Sanitize
        $path = Str::convertToUtf8($path);

        $segs = Arr::whereNotEmpty(preg_split('/[\\/\\\\]/', $path));
        $level = 0;

        foreach ($segs as $seg) {
            if ($seg === '..') {
                $level--;
            } elseif ($seg !== '.') {
                $level++;
            }

            if ($level < 0) {
                return false;
            }
        }

        return true;
    }

    private function path(string $basePath, string $path = ''): string
    {
        return $path === '' ? $basePath : File::normalizePath(join_paths($basePath, $path));
    }

    private function directory(string $basePath, string $path = '', bool $create = true, bool $writeGitignore = false): string
    {
        if ($create) {
            File::ensureDirectoryExists($basePath);

            if ($writeGitignore) {
                File::writeGitignoreFile($basePath);
            }
        }

        return $this->path($basePath, $path);
    }

    private function subdirectory(
        string $basePath,
        string $directory,
        string $path = '',
        bool $create = true,
        bool $writeGitignore = false,
    ): string {
        return $this->directory($this->path($basePath, $directory), $path, $create, $writeGitignore);
    }
}
