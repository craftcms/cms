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
 * Move element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MoveElementEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The ID of the structure the element is being moved within.
     */
    public $structureId;

    /**
     * @var ElementInterface|null The element being moved.
     */
    public $element;
}
