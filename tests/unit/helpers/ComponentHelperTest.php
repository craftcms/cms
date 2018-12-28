<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\base\ComponentInterface;
use craft\errors\MissingComponentException;
use craft\helpers\Component;
use craft\test\mockclasses\components\ComponentExample;
use craft\test\mockclasses\components\DependencyHeavyComponent;
use craft\test\mockclasses\components\ExtendedComponentExample;
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
            'string-to-class-conversion' => [
                function(){
                    return Component::createComponent(ComponentExample::class);
                },
            ],
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
     * @param $desiredParent
     * @param string   $requiredException
     */
    public function testFailedComponentExceptions(array $settings, $desiredParent, string $requiredException)
    {
        $this->tester->expectThrowable(
            $requiredException,
            function () use($settings, $desiredParent){
                Component::createComponent($settings, $desiredParent);
            }
            );
    }

    /**
     * Returns data for failed component creations. Defines settings, the required exception
     * and if the 'type' class must have a class as parent.
     *
     * @return array
     */
    public function failingComponentCreationData()
    {
        return [
            'invalid-required-parent-class' => [
                ['type' => ExtendedComponentExample::class],
                'random\\class\\that\\doesnt\\exist',
                InvalidConfigException::class,
                ],
            'class-doesnt-exist' => [
                [
                    'type' => 'i\\dont\\exist\\as\\a\\class'
                ],
                null,
                MissingComponentException::class
            ],
            'class-not-a-component' => [
                [
                    'type' => self::class
                ],
                null,
                InvalidConfigException::class,
            ],
            'no-params' => [
                [],
                null,
                InvalidConfigException::class,
            ],
            'incorrect-dependancies' => [
                [
                    'type' => DependencyHeavyComponent::class,
                    'notavaliddependancy' => 'value1',
                    'notavaliddependancy2' => 'value2',
                    'settings' => [
                        'notavaliddependancy3' => 'value'
                    ]
                ],
                null,
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