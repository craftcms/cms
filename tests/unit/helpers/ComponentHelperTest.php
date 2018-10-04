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

/**
 * Unit tests for the Component Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ComponentHelperTest extends Unit
{

    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testComponentCreation()
    {
        // Test baseline component creation.
        $this->assertInstanceOf(
            ComponentInterface::class,
            Component::createComponent([
                'type' => ComponentExample::class,
            ])
        );

        $this->tester->expectException(InvalidConfigException::class, function (){
            Component::createComponent([
                'type' => ExtendedComponentExample::class,
            ], 'random\\class\\that\\doesnt\\exist');
        });


        $this->tester->expectException(InvalidConfigException::class, function (){
            Component::createComponent([
                'type' => ExtendedComponentExample::class,
            ], 'random\\class\\that\\doesnt\\exist');
        });

        $this->tester->expectException(MissingComponentException::class, function (){
            Component::createComponent([
                'type' => 'i\\dont\\exist\\as\\a\\class'
            ]);
        });

        $this->tester->expectException(InvalidConfigException::class, function (){
            Component::createComponent([
                'type' => self::class
            ]);
        });

        $this->tester->expectException(InvalidConfigException::class, function (){
            Component::createComponent([]);
        });

        $this->tester->expectException(InvalidConfigException::class, function (){
            Component::createComponent([
                'type' => DependencyHeavyComponent::class,
                'dependancy1' => 'value1',
                'dependancy2' => 'value2',
                'settings' => [
                    'settingsdependency1' => 'value'
                ]
            ]);
        });


        // TODO: Figure out a way to test plugin functionality. Probs create a mock plugin under /_support/mockclasses
    }

    public function testMergingOfSettings()
    {
        $basicComponentArray = [
            'name' => 'Component',
            'description' => 'Lorem ipsum',
            'settings' => [
                'setting1' => 'stuff',
                'setting2' => 'stuff2'
            ]
        ];
        $jsonBasicComponentArray = [
            'name' => 'Component',
            'description' => 'Lorem ipsum',
            'settings' => json_encode([
                'setting1' => 'stuff',
                'setting2' => 'stuff2'
            ])
        ];
        $mergedComponentArray = [
            'name' => 'Component',
            'description' => 'Lorem ipsum',
            'setting1' => 'stuff',
            'setting2' => 'stuff2'
        ];
        $this->assertSame($mergedComponentArray, Component::mergeSettings($basicComponentArray));

        $this->assertSame([], Component::mergeSettings([]));
        $this->assertSame($mergedComponentArray, Component::mergeSettings($jsonBasicComponentArray));
        $this->assertSame($mergedComponentArray, Component::mergeSettings($mergedComponentArray));
        $this->assertSame(['settings'], Component::mergeSettings(['settings']));

        // Ensure nested settings array aren't changed.
        $this->assertSame(
            [
                [
                    'settings' => '22'
                ]
            ],
            Component::mergeSettings([
                [
                    'settings' => '22'
                ]
                ]
            )
        );
    }
}