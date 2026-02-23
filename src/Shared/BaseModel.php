<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    #[\Override]
    protected $guarded = [];

    public const ?string CREATED_AT = 'dateCreated';

    public const ?string UPDATED_AT = 'dateUpdated';

    public const ?string DELETED_AT = 'dateDeleted';
}
