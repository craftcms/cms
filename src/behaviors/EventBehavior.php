<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use yii\base\Behavior;
use yii\base\Event;

/**
 * Event behavior adds events to an object that will carry on to clones of the owner.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class EventBehavior extends Behavior
{
    private array $handledEvents;

    /**
     * @param array<string,callable> $events Event name/handler pairs
     * @param bool $once Whether the events should only be handled once for the owner object
     * @param array $config
     */
    public function __construct(
        private readonly array $events,
        private readonly bool $once = false,
        array $config = [],
    ) {
        if ($this->once) {
            $this->handledEvents = [];
        }

        parent::__construct($config);
    }

    public function events(): array
    {
        return array_map(
            fn(callable $handler) => fn(Event $event) => $this->handleEvent($event, $handler),
            $this->events,
        );
    }

    private function handleEvent(Event $event, callable $handler): void
    {
        if ($this->once) {
            if (isset($this->handledEvents[$event->name])) {
                return;
            }
            $this->handledEvents[$event->name] = true;
        }

        // Send the owner along with the event
        $handler($event, $this->owner);
    }
}
