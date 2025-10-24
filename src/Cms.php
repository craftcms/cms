<?php

declare(strict_types=1);

namespace CraftCms\Cms;

use CraftCms\Cms\Config\GeneralConfig;

final readonly class Cms
{
    public const string NAME = 'Craft CMS';

    public const string VERSION = '6.0.0';

    public const string SCHEMA_VERSION = '6.0.0.0';

    public const string MIN_VERSION_REQUIRED = '5.9.0';

    public static function config(): GeneralConfig
    {
        return app(GeneralConfig::class);
    }
}
