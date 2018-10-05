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

    /**
     * Tests wheter the $callback will evaluate to an instance of the componentInterface.
     *
     * @dataProvider successfulComponentCreationData
     * @param $callback
     */
    public function testSuccessfulComponentCreation(\Closure $callback)
    {
        $this->assertInstanceOf(
            ComponentInterface::class,
            $callback()
        );
    }

    public function successfulComponentCreationData()
    {
        return [
          'succesfull-basic' => [
              function(){
                  return Component::createComponent([
                      'type' => ComponentExample::class,
                  ]);
              },
          ],
            'dependancy-heavy' => [
                function() {
                    return Component::createComponent([
                        'type' => DependencyHeavyComponent::class,
                        'dependency1' => 'value1',
                        'dependency2' => 'value2',
                        'settings' => [
                            'settingsdependency1' => 'value'
                        ]
                    ]);
                }
            ]
        ];
    }

    /**
     * @dataProvider failingComponentCreationData
     * @param \Closure $callback
     * @param string   $requiredException
     */
    public function testFailedComponentExceptions(\Closure $callback, string $requiredException)
    {
        $this->tester->expectException($requiredException, $callback);
    }

    public function failingComponentCreationData()
    {
        return [
            'invalid-required-parent-class' => [
                function(){
                    return Component::createComponent([
                        'type' => ExtendedComponentExample::class,
                    ], 'random\\class\\that\\doesnt\\exist');
                    },
                    InvalidConfigException::class,
                ],
            'class-doesnt-exist' => [
                function(){
                    return Component::createComponent([
                        'type' => 'i\\dont\\exist\\as\\a\\class'
                    ]);
                },
                MissingComponentException::class
            ],
            'class-not-a-component' => [
                function(){
                    return Component::createComponent([
                        'type' => self::class
                    ]);
                },
                InvalidConfigException::class,
            ],
            'no-params' => [
                function(){
                    return Component::createComponent([]);
                },
                InvalidConfigException::class,
            ],
            'incorrect-dependancies' => [
                function(){
                    return Component::createComponent([
                        'type' => DependencyHeavyComponent::class,
                        'notavaliddependancy' => 'value1',
                        'notavaliddependancy2' => 'value2',
                        'settings' => [
                            'notavaliddependancy3' => 'value'
                        ]
                    ]);
                },
                \Exception::class,
            ]

        ];
    }

    public function testComponentCreation()
    {
        // TODO: Figure out a way to test plugin functionality. Probably create a mock plugin under /_support/mockclasses
    }

    /**
     * @dataProvider settingsArraysData
     * @param $mergable
     * @param $result
     */
    public function testSettingsMerging($mergable, $result)
    {
        $this->assertSame($result, Component::mergeSettings($mergable));
    }

    public function settingsArraysData()
    {
        $mergedComponentArray = [
            'name' => 'Component',
            'description' => 'Lorem ipsum',
            'setting1' => 'stuff',
            'setting2' => 'stuff2'
        ];

        return [
            'json-basic' => [
                [
                    'name' => 'Component',
                    'description' => 'Lorem ipsum',
                    'settings' => json_encode([
                        'setting1' => 'stuff',
                        'setting2' => 'stuff2'
                    ])
                ],
                $mergedComponentArray,
            ],
            'basic-component-array' => [
                [
                    'name' => 'Component',
                    'description' => 'Lorem ipsum',
                    'settings' => [
                        'setting1' => 'stuff',
                        'setting2' => 'stuff2'
                    ]
                ],
                $mergedComponentArray
            ],
            'nested-doesnt-change' => [
                [
                    [
                        'name' => 'Component',
                        'settings' => ['setting1' => 'stuff'],
                    ]
                ],
                [
                    [
                        'name' => 'Component',
                        'settings' => ['setting1' => 'stuff'],
                    ]
                ]
            ],
            'key-isnt-removed' => [
                ['settings'],
                ['settings']
            ],
            'empty-array' => [
                [],
                [],
            ]
        ];
    }
}