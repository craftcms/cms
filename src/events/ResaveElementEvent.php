<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use yii\base\Event;

/**
 * Resave Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class ResaveElementEvent extends ResaveElementsEvent
{
    // Properties
    // =========================================================================

    /**
     * @var ElementInterface The element being resaved
     */
    public $element;

    /**
     * @var int The element's position in the query (1-indexed)
     */
    public $position;

    /**
     * @var \Throwable|null $exception The exception that was thrown if any
     */
    public $exception;
}
