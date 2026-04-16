<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;

/**
 * DefineElementDeletionBlockersEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class DefineElementDeletionBlockersEvent extends Event
{
    /**
     * @var ElementInterface[] The elements to be deleted.
     */
    public array $elements;

    /**
     * @var bool Whether the elements will be hard-deleted.
     */
    public bool $hardDelete;

    /**
     * @var array{key:string,summary:string,details?:string,actions:array[]}[] The defined blockers.
     *
     * See [[ElementInterface::deletionBlockers()]] for details.
     */
    public array $blockers = [];
}
