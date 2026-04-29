<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\elements\deletionblockers\DeletionBlockerInterface;
use CraftCms\Cms\Element\ElementCollection;

/**
 * DefineElementDeletionBlockersEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\DefineDeletionBlockers} instead.
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
