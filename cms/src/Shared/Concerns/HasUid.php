<?php

namespace CraftCms\Cms\Shared\Concerns;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Str;

/** @mixin BaseModel */
trait HasUid
{
    public static function bootHasUid(): void
    {
        static::saving(function (BaseModel $model) {
            if (! $model->uid) {
                $model->uid = Str::uuid7();
            }
        });
    }
}
