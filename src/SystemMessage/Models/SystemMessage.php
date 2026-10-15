<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

/**
 * @property string $heading
 * @property array<string, string> $variables The Twig variables the message is rendered with, as name => label pairs. Names may use dot notation for properties, e.g. `user.email`.
 *
 * @since 6.0.0
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
            unset($model->attributes['heading'], $model->attributes['variables']);
        });
    }
}
