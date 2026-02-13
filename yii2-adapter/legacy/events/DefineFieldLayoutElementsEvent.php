<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineFieldLayoutElementsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\FieldLayout\Events\DefineUIElements} instead.
 */
class DefineFieldLayoutElementsEvent extends Event
{
    /**
     * @var \CraftCms\Cms\FieldLayout\FieldLayoutElement[]|string[]|array[] The elements that should be available to the field layout designer.
     */
    public array $elements = [];
}
