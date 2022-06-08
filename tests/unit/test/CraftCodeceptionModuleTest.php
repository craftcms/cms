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
use craft\helpers\ArrayHelper;
use craft\test\mockclasses\components\EventTriggeringComponent;
use craft\test\TestCase;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use stdClass;
use Throwable;
use UnitTester;
use yii\base\Event;

/**
 * CraftCodeceptionModuleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class CraftCodeceptionModuleTest extends TestCase
{
    /**
     * @var UnitTester $tester
     */
    protected UnitTester $tester;

    /**
     *
     */
    public function testEventHandler(): void
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
                        '33' => '55',
                    ],
                ],
            ])
        );
    }

    /**
     *
     */
    public function testEventHandlerWithStdClass(): void
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
                        'a' => '22',
                    ],
                ],
            ])
        );
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function testAssertElementsExist(): void
    {
        $configArray = [
            'active' => true,
            'firstName' => 'john',
            'lastName' => 'smith',
            'username' => 'user2',
            'email' => 'user2@crafttest.com',
        ];

        $user = new User($configArray);
        $this->tester->saveElement($user);

        $this->tester->assertElementsExist(User::class, ArrayHelper::without($configArray, 'active'));
        $this->tester->deleteElement($user);
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function testAssertElementFails(): void
    {
        $configArray = [
            'active' => true,
            'firstName' => 'john',
            'lastName' => 'smith',
            'username' => 'user2',
            'email' => 'user2@crafttest.com',
        ];

        $user = new User($configArray);
        $this->tester->saveElement($user);

        $this->tester->assertTestFails(function() use ($configArray) {
            $this->tester->assertElementsExist(User::class, ArrayHelper::without($configArray, 'active'), 2);
        });

        $this->tester->deleteElement($user);
    }

    /**
     * @throws Throwable
     * @throws InvalidElementException
     * @throws \yii\base\Exception
     */
    public function testAssertElementExistsWorksWithMultiple(): void
    {
        $configArray = [
            'active' => true,
            'firstName' => 'john',
            'lastName' => 'smith',
            'username' => 'user2',
            'email' => 'user2@crafttest.com',
        ];

        $user = new User($configArray);

        $this->tester->saveElement($user);

        $dupeConfig = ['username' => 'user3', 'email' => 'user3@crafttest.com'];
        $dupeUser = Craft::$app->getElements()->duplicateElement($user, $dupeConfig);

        $this->tester->assertElementsExist(User::class, ArrayHelper::without($configArray, 'active'), 1);
        $this->tester->assertElementsExist(User::class, array_merge(ArrayHelper::without($configArray, 'active'), $dupeConfig), 1);

        $this->tester->deleteElement($user);
        $this->tester->deleteElement($dupeUser);
    }

    /**
     * @throws Exception
     */
    public function testDateTimeCompare(): void
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
        $otherDateTime = new DateTime('now', new DateTimeZone('UTC'));
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
