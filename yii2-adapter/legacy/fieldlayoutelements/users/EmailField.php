<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * EmailField represents an Email field that can be included in the user field layout.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\users\EmailField} instead.
     */
    class EmailField
    {
    }
}

class_alias(\CraftCms\Cms\FieldLayout\LayoutElements\users\EmailField::class, EmailField::class);
