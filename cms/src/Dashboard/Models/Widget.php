<?php

/**
 * @since 6.0.0
 */

namespace CraftCms\Cms\Dashboard\Models;

use CraftCms\Cms\Support\BaseModel;

class Widget extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'sortOrder' => 'integer',
        'colspan' => 'integer',
        'settings' => 'json',
        'enabled' => 'boolean',
        'dateCreated' => 'immutable_datetime',
        'dateUpdated' => 'immutable_datetime',
    ];
}
