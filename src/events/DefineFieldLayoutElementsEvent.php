<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\base\FieldLayoutElement;

/**
 * DefineFieldLayoutElementsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DefineFieldLayoutElementsEvent extends Event
{
    /**
     * @var FieldLayoutElement[]|string[]|array[] The elements that should be available to the field layout designer.
     */
    public array $elements = [];
}
