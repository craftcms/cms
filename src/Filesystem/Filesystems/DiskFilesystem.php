<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Support\Str;
use Override;

use function CraftCms\Cms\t;

/**
 * @internal This class is used when a Volume is configured to use a Laravel disk.
 */
final class DiskFilesystem extends Filesystem
{
    #[Override]
    protected static bool $showHasUrlSetting = false;

    #[Override]
    protected static bool $showUrlSetting = false;

    public ?string $disk = null;

    #[Override]
    public static function displayName(): string
    {
        return t('Laravel Disk');
    }

    public function __construct($config = [])
    {
        if (isset($config['disk']) && is_string($config['disk'])) {
            $disk = $config['disk'];
            $config['name'] ??= $disk;
            $config['handle'] ??= "disk:$disk";
        }

        parent::__construct($config);
    }

    #[Override]
    public function getRootUrl(): ?string
    {
        if (! $this->disk) {
            return null;
        }

        $url = config("filesystems.disks.$this->disk.url");
        if (! is_string($url) || $url === '') {
            return null;
        }

        return Str::finish($url, '/');
    }

    #[Override]
    public function getDiskConfig(): array
    {
        if (! $this->disk) {
            throw new FilesystemException('The Laravel disk name is missing.');
        }

        $diskConfig = config("filesystems.disks.$this->disk");
        if (! is_array($diskConfig)) {
            throw new FilesystemException("Invalid Laravel disk configuration for [$this->disk].");
        }

        return $diskConfig;
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), ['disk' => ['required', 'string']]);
    }
}
