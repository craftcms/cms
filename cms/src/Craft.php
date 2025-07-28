<?php

namespace CraftCms\Cms;

use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Support\Facades\Config;

class Craft
{
    public static function generalConfig(): GeneralConfig
    {
        return Config::get('craft.general');
    }
}
