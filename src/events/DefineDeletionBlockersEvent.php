<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\elements\deletionblockers\DeletionBlockerInterface;

/**
 * DefineDeletionBlockersEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class DefineDeletionBlockersEvent extends Event
{
    /**
     * @var string The subject type to be deleted, such as `element`, `field`, or `volume`.
     * @see \craft\services\DeletionBlockers::getSubjectTypes()
     */
    public string $type;

    /**
     * @var mixed The subject to be deleted, such as a field or volume. For the `element` type, it’s an
     * [[\craft\elements\ElementCollection]] of the elements to be deleted.
     */
    public mixed $subject;

    /**
     * @var bool Whether the elements will be hard-deleted. Only applies to the `element` type.
     */
    public bool $hardDelete = false;

    /**
     * @var DeletionBlockerInterface[] The defined blockers.
     */
    public array $blockers = [];
}
