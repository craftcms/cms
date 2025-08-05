<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Dashboard\Models;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Widgets\MissingWidget;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\BaseModel;

class Widget extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'sortOrder' => 'integer',
        'colspan' => 'integer',
        'settings' => 'json',
        'enabled' => 'boolean',
        'dateCreated' => 'datetime',
        'dateUpdated' => 'datetime',
    ];

    public function toWidget(): WidgetInterface
    {
        $class = $this->type;

        try {
            $widget = new $class;

            $attributes = Arr::except($this->toArray(), ['settings']);
            $settings = $this->settings ?? [];
            $attributes = array_merge($attributes, $settings);

            foreach ($attributes as $key => $value) {
                if (! property_exists($widget, $key)) {
                    continue;
                }

                try {
                    $widget->{$key} = $value;
                } catch (\Throwable) {
                }
            }

            return $widget;
        } catch (\Throwable $e) {
            $widget = new MissingWidget;
            $widget->errorMessage = $e->getMessage();

            return $widget;
        }
    }
}
