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
 * Resave Elements event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class ResaveElementsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var ElementQueryInterface The element query the elements will be pulled from.
     */
    public $query;
}
