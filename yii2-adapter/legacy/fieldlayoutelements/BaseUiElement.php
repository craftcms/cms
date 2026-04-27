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
 * BaseUiElement is the base class for UI elements that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement} instead.
 */
abstract class BaseUiElement extends \CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement
{
    use \craft\base\LegacyEventConstants;
}
