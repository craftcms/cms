<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * CustomField represents a custom field that can be included in field layouts.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\CustomField} instead.
     */
    class CustomField
    {
    }
}

class_alias(\CraftCms\Cms\FieldLayout\LayoutElements\CustomField::class, CustomField::class);
