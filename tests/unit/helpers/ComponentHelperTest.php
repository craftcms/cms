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
use craftunit\support\helpers\UnitExceptionHandler;
use craftunit\support\mockclasses\components\ComponentExample;
use craftunit\support\mockclasses\components\DependencyHeavyComponent;
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


        $this->assertTrue(
            UnitExceptionHandler::ensureException(
                function () {
                    Component::createComponent([
                        'type' => ExtendedComponentExample::class,
                    ], 'random\\class\\that\\doesnt\\exist');
                },
                InvalidConfigException::class
            )
        );

        $this->assertTrue(
            UnitExceptionHandler::ensureException(
                function () {
                    Component::createComponent([
                        'type' => 'i\\dont\\exist\\as\\a\\class'
                    ]);
                },
                MissingComponentException::class
            )
        );

        $this->assertTrue(
            UnitExceptionHandler::ensureException(
                function () {
                    Component::createComponent([
                        'type' => self::class
                    ]);
                },
                InvalidConfigException::class
            )
        );

        $this->assertTrue(
            UnitExceptionHandler::ensureException(
                function () {
                    Component::createComponent([]);
                },
                InvalidConfigException::class
            )
        );

        $this->assertTrue(
            UnitExceptionHandler::ensureException(
                function () {
                    Component::createComponent([]);
                },
                InvalidConfigException::class
            )
        );

        $this->assertTrue(
            UnitExceptionHandler::ensureException(
                function () {
                    Component::createComponent([
                        'type' => DependencyHeavyComponent::class,
                        'dependancy1' => 'value1',
                        'dependancy2' => 'value2',
                        'settings' => [
                            'settingsdependency1' => 'value'
                        ]
                    ]);
                },
                InvalidConfigException::class
            )
        );


        // TODO: Figure out a way to test plugin functionality
    }

    public function testMergingOfSettings()
    {

    }
}