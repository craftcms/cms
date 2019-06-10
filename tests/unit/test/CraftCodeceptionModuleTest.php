<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use craft\elements\Entry;
use craft\elements\User;
use craft\test\mockclasses\components\EventTriggeringComponent;
use crafttests\fixtures\EntryFixture;
use PHPUnit\Framework\ExpectationFailedException;
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

    /**
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
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

        \Craft::$app->getElements()->saveElement($user);

        $this->tester->assertElementsExist(User::class, $configArray);
    }

    /**
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
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

        \Craft::$app->getElements()->saveElement($user);

        $this->tester->assertTestFails(function() use ($configArray) {
            $this->tester->assertElementsExist(User::class, $configArray, 2);
        });
    }

    /**
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \craft\errors\InvalidElementException
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

        \Craft::$app->getElements()->saveElement($user);

        \Craft::$app->getElements()->duplicateElement($user);

        $this->tester->assertElementsExist(User::class, $configArray, 2);
    }
}
