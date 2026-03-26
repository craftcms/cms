<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

/**
 * Markdown represents a UI element based on Markdown content can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\Markdown} instead.
 */
class Markdown extends \CraftCms\Cms\FieldLayout\LayoutElements\Markdown
{
    use \craft\base\LegacyEventConstants;
}
