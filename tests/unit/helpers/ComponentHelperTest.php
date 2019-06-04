<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Closure;
use Codeception\Test\Unit;
use craft\base\ComponentInterface;
use craft\errors\MissingComponentException;
use craft\helpers\Component;
use craft\test\mockclasses\components\ComponentExample;
use craft\test\mockclasses\components\DependencyHeavyComponentExample;
use craft\test\mockclasses\components\ExtendedComponentExample;
use Exception;
use UnitTester;
use yii\base\InvalidConfigException;

/**
 * Unit tests for the Component Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ComponentHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * Tests whether the $callback will evaluate to an instance of the componentInterface.
     *
     * @dataProvider successfulComponentCreationDataProvider
     *
     * @param $callback
     */
    public function testSuccessfulComponentCreation(Closure $callback)
    {
        $this->assertInstanceOf(
            ComponentInterface::class,
            $callback()
        );
    }

    /**
     * @dataProvider failingComponentCreationDataProvider
     *
     * @param array $settings
     * @param $desiredParent
     * @param string $requiredException
     */
    public function testFailedComponentExceptions(array $settings, $desiredParent, string $requiredException)
    {
        $this->tester->expectThrowable(
            $requiredException,
            function() use ($settings, $desiredParent) {
                Component::createComponent($settings, $desiredParent);
            }
        );
    }

    /**
     * @todo Figure out a way to test plugin functionality. Probably create a mock plugin under /_support/mockclasses
     */
    public function testComponentCreation()
    {

    }

    /**
     * @dataProvider settingsArraysDataProvider
     *
     * @param $mergeable
     * @param $result
     */
    public function testSettingsMerging($mergeable, $result)
    {
        $this->assertSame($result, Component::mergeSettings($mergeable));
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function successfulComponentCreationDataProvider(): array
    {
        return [
            'string-to-class-conversion' => [
                function() {
                    return Component::createComponent(ComponentExample::class);
                },
            ],
            'successful-basic' => [
                function() {
                    return Component::createComponent([
                        'type' => ComponentExample::class,
                    ]);
                },
            ],
            'dependency-heavy' => [
                function() {
                    return Component::createComponent([
                        'type' => DependencyHeavyComponentExample::class,
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
     * Returns data for failed component creations. Defines settings, the required exception
     * and if the 'type' class must have a class as parent.
     *
     * @return array
     */
    public function failingComponentCreationDataProvider(): array
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
            'incorrect-dependencies' => [
                [
                    'type' => DependencyHeavyComponentExample::class,
                    'notavaliddependency' => 'value1',
                    'notavaliddependency2' => 'value2',
                    'settings' => [
                        'notavaliddependency3' => 'value'
                    ]
                ],
                null,
                Exception::class,
            ]

        ];
    }

    /**
     * @return array
     */
    public function settingsArraysDataProvider(): array
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
