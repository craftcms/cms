<?php

namespace CraftCms\Cms\Shared;

use Illuminate\Database\Eloquent\Model;

/** @since 6.0.0 */
class BaseModel extends Model
{
    public const ?string CREATED_AT = 'dateCreated';

    public const ?string UPDATED_AT = 'dateUpdated';

    public const ?string DELETED_AT = 'dateDeleted';
}
