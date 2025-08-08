<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
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
