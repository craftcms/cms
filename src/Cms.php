<?php

declare(strict_types=1);

namespace CraftCms\Cms;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;

final readonly class Cms
{
    public const string NAME = 'Craft CMS';

    public const string VERSION = '6.0.0';

    public const string SCHEMA_VERSION = '6.0.0.0';

    public const string MIN_VERSION_REQUIRED = '5.9.0';

    public static function config(): GeneralConfig
    {
        return resolve(GeneralConfig::class) ?? GeneralConfig::create();
    }

    public static function systemName(): string
    {
        $name = Env::parse(ProjectConfig::get('system.name'));

        if ($name !== null) {
            return $name;
        }

        try {
            $name = Sites::getPrimarySite()->getName();
        } catch (SiteNotFoundException) {
            $name = null;
        }

        return $name ?: config('app.name', 'Craft');
    }
}
