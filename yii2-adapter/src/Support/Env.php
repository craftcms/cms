<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Support;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Env as CoreEnv;

class Env extends CoreEnv
{
    public static function parse(?string $value): ?string
    {
        $value = parent::parse($value);

        return is_string($value) && str_starts_with($value, '@')
            ? Aliases::get($value)
            : $value;
    }
}
