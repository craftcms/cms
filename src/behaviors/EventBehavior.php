<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use craft\base\Event;
use yii\base\Behavior;

/**
 * Event behavior adds events to an object that will carry on to clones of the owner.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class EventBehavior extends Behavior
{
    /**
     * @param array<string,callable> $events
     * @param array $config
     */
    public function __construct(
        private readonly array $events,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function events(): array
    {
        // Pass along the configured event handlers, but send the owner along with the event
        return array_map(
            fn(callable $handler) => fn(Event $event) => $handler($event, $this->owner),
            $this->events,
        );
    }
}
