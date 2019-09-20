<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use Craft;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidElementException;
use craft\test\mockclasses\components\EventTriggeringComponent;
use Exception;
use stdClass;
use Throwable;
use UnitTester;
use yii\base\Event;
use Codeception\Test\Unit;
use DateTime;
use DateTimeZone;
use DateInterval;

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
                    'desiredClass' => stdClass::class,
                    'desiredValue' => [
                        'a' => '22'
                    ]
                ]
            ])
        );
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function testAssertElementsExist()
    {
        $configArray = [
            'firstName' => 'john',
            'lastName' => 'smith',
            'username' => 'user2',
            'email' => 'user2@crafttest.com',
        ];

        $user = new User($configArray);

        Craft::$app->getElements()->saveElement($user);

        $this->tester->assertElementsExist(User::class, $configArray);
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function testAssertElementFails()
    {
        $configArray = [
            'firstName' => 'john',
            'lastName' => 'smith',
            'username' => 'user2',
            'email' => 'user2@crafttest.com',
        ];

        $user = new User($configArray);

        Craft::$app->getElements()->saveElement($user);

        $this->tester->assertTestFails(function() use ($configArray) {
            $this->tester->assertElementsExist(User::class, $configArray, 2);
        });
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidElementException
     * @throws \yii\base\Exception
     */
    public function testAssertElementExistsWorksWithMultiple()
    {
        $configArray = [
            'firstName' => 'john',
            'lastName' => 'smith',
            'username' => 'user2',
            'email' => 'user2@crafttest.com',
        ];

        $user = new User($configArray);

        Craft::$app->getElements()->saveElement($user);

        $dupeConfig = ['username' => 'user3', 'email' => 'user3@crafttest.com'];
        Craft::$app->getElements()->duplicateElement($user, $dupeConfig);

        $this->tester->assertElementsExist(User::class, $configArray, 1);
        $this->tester->assertElementsExist(User::class, array_merge($configArray, $dupeConfig), 1);
    }

    /**
     * @throws Exception
     */
    public function testDateTimeCompare()
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        $this->tester->assertEqualDates(
            $this,
            $dateTime->format('Y-m-d H:i:s'),
            $dateTime->format('Y-m-d H:i:s')
        );

        $otherDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $otherDateTime->add(new DateInterval('P1D'));

        $this->tester->assertTestFails(function() use ($dateTime, $otherDateTime) {
            $this->tester->assertEqualDates(
                $this,
                $dateTime->format('Y-m-d H:i:s'),
                $otherDateTime->format('Y-m-d H:i:s')
            );
        });

        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $otherDateTime =  new DateTime('now', new DateTimeZone('UTC'));
        $otherDateTime->add(new DateInterval('PT1S'));
        $this->tester->assertEqualDates(
            $this,
            $dateTime->format('Y-m-d H:i:s'),
            $otherDateTime->format('Y-m-d H:i:s'),
            3
        );

        // No delta. No Bueno.
        $this->tester->assertTestFails(function() use ($dateTime, $otherDateTime) {
            $this->tester->assertEqualDates(
                $this,
                $dateTime->format('Y-m-d H:i:s'),
                $otherDateTime->format('Y-m-d H:i:s'),
                0.0
            );
        });
    }
}
