<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * ElementIndexAvailableTableAttributesEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.9
 */
class ElementIndexAvailableTableAttributesEvent extends Event
{
    /**
     * @var array The element type.
     */
    public $elementType;

    /**
     * @var bool Whether or not to include fields.
     */
    public $includeFields;

    /**
     * @var array The collection of available table attributes.
     */
    public $attributes;
}
