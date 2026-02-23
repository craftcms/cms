<?php

declare(strict_types=1);

namespace craft\models;

use CraftCms\Cms\FieldLayout\FieldLayout;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * FieldLayoutTab model class.
     *
     * @property \CraftCms\Cms\FieldLayout\FieldLayoutElement[]|null $elements The tab’s layout elements
     * @property FieldLayout|null $layout The tab’s layout
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayoutTab} instead.
     */
    class FieldLayoutTab
    {
    }
}

class_alias(\CraftCms\Cms\FieldLayout\FieldLayoutTab::class, FieldLayoutTab::class);
