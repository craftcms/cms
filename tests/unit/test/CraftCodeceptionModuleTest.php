<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use craft\test\mockclasses\components\EventTriggeringComponent;
use UnitTester;
use yii\base\Event;
use Codeception\Test\Unit;

/**
 * CraftCodeceptionModuleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class CraftCodeceptionModuleTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester $tester
     */
    protected $tester;

    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testEventHandler()
    {
        $component = new EventTriggeringComponent();
        $this->tester->expectEvent(
            EventTriggeringComponent::class,
            'event',
            function() use ($component) {
                $component->triggerEvent();
            },
            Event::class,
            $this->tester->createEventItems([
                [
                    'type' => 'othervalue',
                    'eventPropName' => 'sender',
                    'desiredValue' => [
                        '22' => '44',
                        '33' => '55'
                    ]
                ]
            ])
            );
    }

    /**
     *
     */
    public function testEventHandlerWithStdClass()
    {
        $component = new EventTriggeringComponent();
        $this->tester->expectEvent(
            EventTriggeringComponent::class,
            'event',
            function() use ($component) {
                $component->triggerEventWithStdClass();
            },
            Event::class,
            $this->tester->createEventItems([
                [
                    'type' => 'class',
                    'eventPropName' => 'sender',
                    'desiredClass' => \stdClass::class,
                    'desiredValue' => [
                        'a' => '22'
                    ]
                ]
            ])
        );
    }
}
