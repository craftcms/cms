<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\assets;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * AssetTitleField represents a Title field that can be included within a volume's field layout designer.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     *
     * @since 3.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\assets\AssetTitleField} instead.
     */
    class AssetTitleField
    {
    }
}

class_alias(\CraftCms\Cms\FieldLayout\LayoutElements\assets\AssetTitleField::class, AssetTitleField::class);
