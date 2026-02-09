<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\entries;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * EntryTitleField represents a Title field that can be included within an entry type's field layout designer.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\entries\EntryTitleField} instead.
     */
    class EntryTitleField
    {
    }
}

class_alias(\CraftCms\Cms\FieldLayout\LayoutElements\entries\EntryTitleField::class, EntryTitleField::class);
