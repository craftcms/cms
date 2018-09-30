<?php
/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 29/09/2018
 * Time: 12:44
 */

namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\base\ComponentInterface;
use craft\errors\MissingComponentException;
use craft\helpers\Component;
use craftunit\support\mockclasses\components\ComponentExample;
use craftunit\support\mockclasses\components\ExtendedComponentExample;
use yii\base\InvalidConfigException;

class ComponentHelperTest extends Unit
{
    public function testComponentCreation()
    {
        // Test baseline component creation.
        $this->assertInstanceOf(
            ComponentInterface::class,
            Component::createComponent([
                'type' => ComponentExample::class,
            ])
        );

        // Test that an invalidConfig is thrown if the class doesnt implement the correct parent.
        $exceptionThrown = false;
        try {
            Component::createComponent([
                'type' => ExtendedComponentExample::class,
            ], 'random\\class\\that\\doesnt\\exist');
        } catch (InvalidConfigException $exception) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Test that unfound components throw a MissingComponentException
        $exceptionThrown = false;
        try {
            Component::createComponent([
                'type' => 'i\\dont\\exist\\as\\a\\class'
            ]);
        } catch (MissingComponentException $exception) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Test that Components have to implement component interface
        $exceptionThrown = false;
        try {
            Component::createComponent([
                'type' => self::class
            ]);
        } catch (InvalidConfigException $exception) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Test that Components have to add a type in the provided array.
        $exceptionThrown = false;
        try {
            Component::createComponent([]);
        } catch (InvalidConfigException $exception) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // TODO: Figure out a way to test plugin functionality
    }

    public function testMergingOfSettings()
    {

    }
}