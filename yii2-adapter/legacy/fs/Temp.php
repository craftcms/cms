<?php

declare(strict_types=1);

namespace craft\fs;

use Craft;
use Override;

/**
 * Temp represents a temporary filesystem.
 *
 * @since 4.0.0
 * @deprecated 6.0.0
 */
class Temp extends Local
{
    public bool $hasUrls = false;

    #[Override]
    public static function displayName(): string
    {
        return 'Temp';
    }

    public function __construct($config = [])
    {
        $config['path'] ??= Craft::$app->getPath()->getTempAssetUploadsPath();
        $config['name'] ??= Craft::t('app', 'Temporary Uploads');

        parent::__construct($config);
    }

    #[Override]
    public function getSettingsHtml(): ?string
    {
        return null;
    }
}
