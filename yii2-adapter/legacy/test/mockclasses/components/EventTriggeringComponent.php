<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\components;

use craft\base\Component;
use stdClass;
use yii\base\Event;

/**
 * Class EventTriggeringComponent.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class EventTriggeringComponent extends Component
{
    /**
     * Triggers an event.
     */
    public function triggerEvent(): void
    {
        $event = new Event();
        $event->sender = (object)['22' => '44', '33' => '55'];

        $this->trigger('event', $event);
    }

    /**
     * Triggers an event with standard class
     */
    public function triggerEventWithStdClass(): void
    {
        $stdClass = new stdClass();
        $stdClass->a = '22';

        $event = new Event();
        $event->sender = $stdClass;

        $this->trigger('event', $event);
    }
}
