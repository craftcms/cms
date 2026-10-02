<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @since 6.0.0
 */
class BasePivot extends Pivot
{
    public const ?string CREATED_AT = 'dateCreated';

    public const ?string UPDATED_AT = 'dateUpdated';

    public const ?string DELETED_AT = 'dateDeleted';
}
