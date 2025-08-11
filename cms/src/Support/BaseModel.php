<?php

namespace CraftCms\Cms\Support;

use Illuminate\Database\Eloquent\Model;

/** @since 6.0.0 */
class BaseModel extends Model
{
    public const CREATED_AT = 'dateCreated';

    public const UPDATED_AT = 'dateUpdated';
}
