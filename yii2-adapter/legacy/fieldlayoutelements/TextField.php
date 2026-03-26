<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

/**
 * TextField represents a text field that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\TextField} instead.
 */
class TextField extends \CraftCms\Cms\FieldLayout\LayoutElements\TextField
{
    use \craft\base\LegacyEventConstants;
}
