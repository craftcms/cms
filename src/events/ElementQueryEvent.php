<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\elements\db\ElementQueryInterface;

/**
 * Element query event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class ElementQueryEvent extends Event
{
    /**
     * @var ElementQueryInterface The element query.
     */
    public ElementQueryInterface $query;
}
