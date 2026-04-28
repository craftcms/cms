<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

/**
 * Template represents a UI element based on a custom template that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\Template} instead.
 */
class Template extends \CraftCms\Cms\FieldLayout\LayoutElements\Template
{
    use \craft\base\LegacyEventConstants;
}
