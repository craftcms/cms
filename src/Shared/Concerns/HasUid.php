<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Concerns;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Str;

/**
 * @property string $uid
 *
 * @mixin BaseModel
 */
trait HasUid
{
    public static function bootHasUid(): void
    {
        static::saving(function ($model) {
            if (! $model->uid) {
                $model->uid = Str::uuid7()->toString();
            }
        });
    }
}
