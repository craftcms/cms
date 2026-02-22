<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Env;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\FilesystemManager;

/**
 * Registers Craft filesystem handles as Laravel disk configs
 * and keeps them synchronized with the Project Config.
 */
#[Singleton]
final readonly class DiskRegistry
{
    public const string PREFIX = 'craft-fs-';

    public const string BRIDGE_DRIVER = 'craft-fs-bridge';

    public function __construct(
        private ConfigRepository $config,
        private FilesystemManager $filesystems,
        private ProjectConfig $projectConfig,
    ) {}

    public function toDiskName(string $handle): string
    {
        return self::PREFIX.$handle;
    }

    public function isCraftDiskName(string $diskName): bool
    {
        return str_starts_with($diskName, self::PREFIX);
    }

    public function sync(): void
    {
        [$manualDisks, $oldCraftDiskNames] = $this->splitDisks($this->currentDiskConfigs());
        $craftDisks = $this->craftDisksFromProjectConfig();

        $this->config->set('filesystems.disks', [
            ...$manualDisks,
            ...$craftDisks,
        ]);

        $this->forgetDisks([
            ...$oldCraftDiskNames,
            ...array_keys($craftDisks),
        ]);
    }

    /**
     * Registers a single Craft filesystem as a Laravel disk.
     */
    public function registerDisk(string $handle, ?array $filesystemConfig = null): void
    {
        $diskName = $this->toDiskName($handle);
        $diskConfig = [
            'driver' => self::BRIDGE_DRIVER,
            'fsHandle' => $handle,
        ];

        if (is_array($filesystemConfig)) {
            $url = $this->filesystemUrl($filesystemConfig);
            if ($url !== null) {
                $diskConfig['url'] = $url;
            }
        }

        $diskConfigs = $this->currentDiskConfigs();
        $diskConfigs[$diskName] = $diskConfig;
        $this->config->set('filesystems.disks', $diskConfigs);
        $this->filesystems->forgetDisk($diskName);
    }

    public function purge(string $handle): void
    {
        $diskName = $this->toDiskName($handle);

        $diskConfigs = $this->config->get('filesystems.disks', []);
        if (! is_array($diskConfigs) || ! array_key_exists($diskName, $diskConfigs)) {
            $this->filesystems->forgetDisk($diskName);

            return;
        }

        unset($diskConfigs[$diskName]);

        $this->config->set('filesystems.disks', $diskConfigs);
        $this->filesystems->forgetDisk($diskName);
    }

    private function currentDiskConfigs(): array
    {
        $diskConfigs = $this->config->get('filesystems.disks', []);

        if (! is_array($diskConfigs)) {
            return [];
        }

        return $diskConfigs;
    }

    /**
     * @return array{array<string,mixed>,array<int,string>}
     */
    private function splitDisks(array $diskConfigs): array
    {
        $manualDisks = [];
        $craftDiskNames = [];

        foreach ($diskConfigs as $diskName => $diskConfig) {
            if (! is_string($diskName)) {
                continue;
            }

            if ($this->isCraftDiskName($diskName)) {
                $craftDiskNames[] = $diskName;

                continue;
            }

            $manualDisks[$diskName] = $diskConfig;
        }

        return [$manualDisks, $craftDiskNames];
    }

    /**
     * @param  array<int,string>  $diskNames
     */
    private function forgetDisks(array $diskNames): void
    {
        $diskNames = array_values(array_unique($diskNames));
        if ($diskNames === []) {
            return;
        }

        $this->filesystems->forgetDisk($diskNames);
    }

    /**
     * @return array<string,array{driver:string,fsHandle:string,url?:string}>
     */
    private function craftDisksFromProjectConfig(): array
    {
        $craftDisks = [];

        $projectConfig = $this->projectConfig->get(ProjectConfig::PATH_FS);
        if (! is_array($projectConfig)) {
            return $craftDisks;
        }

        foreach ($projectConfig as $handle => $filesystemConfig) {
            if (! is_string($handle)) {
                continue;
            }
            if ($handle === '') {
                continue;
            }
            $diskConfig = [
                'driver' => self::BRIDGE_DRIVER,
                'fsHandle' => $handle,
            ];

            if (is_array($filesystemConfig)) {
                $url = $this->filesystemUrl($filesystemConfig);
                if ($url !== null) {
                    $diskConfig['url'] = $url;
                }
            }

            $craftDisks[$this->toDiskName($handle)] = $diskConfig;
        }

        return $craftDisks;
    }

    private function filesystemUrl(array $filesystemConfig): ?string
    {
        if (
            ! ($filesystemConfig['hasUrls'] ?? false) ||
            ! is_string($filesystemConfig['url'] ?? null)
        ) {
            return null;
        }

        $url = Env::parse($filesystemConfig['url']);
        if (! is_string($url) || $url === '') {
            return null;
        }

        return rtrim($url, '/');
    }
}
