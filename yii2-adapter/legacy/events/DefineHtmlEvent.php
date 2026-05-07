<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineHtmlEvent is used to define the HTML for a UI component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\ElementAdditionalButtonsResolving}, {@see \CraftCms\Cms\Element\Events\ElementSidebarHtmlResolving}, or {@see \CraftCms\Cms\Element\Events\ElementMetaFieldsHtmlResolving} instead.
 */
class DefineHtmlEvent extends Event
{
    /**
     * @var string The UI component’s HTML
     */
    public string $html = '';

    /**
     * @var bool Whether the HTML should be static (non-interactive)
     * @since 4.0.0
     */
    public bool $static = false;
}
