<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\entries;

/**
 * EntryTitleField represents a Title field that can be included within an entry type's field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\entries\EntryTitleField} instead.
 */
class EntryTitleField extends \CraftCms\Cms\FieldLayout\LayoutElements\entries\EntryTitleField
{
    use \craft\base\LegacyEventConstants;
}
