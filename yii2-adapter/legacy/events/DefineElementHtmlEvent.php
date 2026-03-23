<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * DefineElementHtmlEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Cp\Events\DefineElementChipHtml} or {@see \CraftCms\Cms\Cp\Events\DefineElementCardHtml} instead.
 */
class DefineElementHtmlEvent extends ElementEvent
{
    /**
     * @var string The context the element is going to be shown in (`index`, `field`, etc.).
     */
    public string $context;

    /**
     * @var string The element’s pre-rendered chip or card HTML
     */
    public string $html;
}
