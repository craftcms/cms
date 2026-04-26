<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\elements\deletionblockers\DeletionBlockerInterface;
use craft\elements\ElementCollection;

/**
 * DefineElementDeletionBlockersEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class DefineElementDeletionBlockersEvent extends Event
{
    /**
     * @var ElementCollection The elements to be deleted.
     */
    public ElementCollection $elements;

    /**
     * @var bool Whether the elements will be hard-deleted.
     */
    public bool $hardDelete;

    /**
     * @var DeletionBlockerInterface[] The defined blockers.
     */
    public array $blockers = [];
}
