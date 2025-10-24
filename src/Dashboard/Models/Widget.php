<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Models;

use CraftCms\Cms\Shared\BaseModel;

class Widget extends BaseModel
{
    protected $casts = [
        'sortOrder' => 'integer',
        'colspan' => 'integer',
        'settings' => 'json',
        'enabled' => 'boolean',
        'dateCreated' => 'immutable_datetime',
        'dateUpdated' => 'immutable_datetime',
    ];
}
