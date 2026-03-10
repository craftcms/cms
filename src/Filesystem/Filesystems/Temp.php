<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use CraftCms\Cms\Support\Facades\Path;

use function CraftCms\Cms\t;

final class Temp extends Local
{
    #[\Override]
    public bool $hasUrls = false;

    #[\Override]
    public static function displayName(): string
    {
        return 'Temp';
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // Config normalization
        $config['path'] ??= Path::tempAssetUploads();
        $config['name'] ??= t('Temporary Uploads');

        parent::__construct($config);
    }

    #[\Override]
    public function getSettingsHtml(): ?string
    {
        return null;
    }
}
