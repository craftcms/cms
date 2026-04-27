<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

/**
 * @property string $heading
 */
class SystemMessage extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::SYSTEMMESSAGES;

    #[\Override]
    protected static function booted(): void
    {
        self::saving(function (self $model) {
            unset($model->attributes['heading']);
        });
    }
}
