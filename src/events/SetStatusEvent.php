<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\db\ElementQueryInterface;
use yii\base\Event;

/**
 * Set Status element action event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SetStatusEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var ElementQueryInterface|null The element query representing the elements that just got updated.
     */
    public $elementQuery;

    /**
     * @var array|null The element IDs that are getting updated.
     */
    public $elementIds;

    /**
     * @var string|null The status the elements are getting set to.
     */
    public $status;
}
