<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementEvent extends Event
{
    /**
     * @var ElementInterface|null The element model associated with the event.
     */
    public $element;

    /**
     * @var bool Whether the element is brand new
     */
    public $isNew = false;
}
