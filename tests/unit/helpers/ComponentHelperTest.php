<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\base\ComponentInterface;
use craft\base\FieldInterface;
use craft\base\FieldLayoutElement;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fields\PlainText;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\test\mockclasses\components\ComponentExample;
use craft\test\mockclasses\components\DependencyHeavyComponentExample;
use craft\test\mockclasses\components\ExtendedComponentExample;
use craft\test\TestCase;
use Exception;
use Throwable;
use UnitTester;
use yii\base\InvalidConfigException;

/**
 * Unit tests for the Component Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ComponentHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider validateComponentClassDataProvider
     * @param bool $expected
     * @param string $class
     * @phpstan-param class-string $class
     * @param string|null $instanceOf
     * @phpstan-param class-string|null $instanceOf
     * @param string $exceptionClass
     * @phpstan-param class-string $exceptionClass
     */
    public function testValidateComponentClass(bool $expected, string $class, ?string $instanceOf = null, string $exceptionClass = Throwable::class): void
    {
        self::assertSame($expected, Component::validateComponentClass($class, $instanceOf));
        if (!$expected) {
            self::expectException($exceptionClass);
            Component::validateComponentClass($class, $instanceOf, true);
        }
    }

    /**
     * Tests whether the $callback will evaluate to an instance of the componentInterface.
     *
     * @dataProvider successfulComponentCreationDataProvider
     * @param callable $callback
     */
    public function testSuccessfulComponentCreation(callable $callback): void
    {
        self::assertInstanceOf(
            ComponentInterface::class,
            $callback()
        );
    }

    /**
     * @dataProvider failingComponentCreationDataProvider
     * @param array $settings
     * @param string|null $desiredParent
     * @param string $requiredException
     */
    public function testFailedComponentExceptions(array $settings, ?string $desiredParent, string $requiredException): void
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
    public function testComponentCreation(): void
    {
    }

    /**
     * @dataProvider mergeSettingsDataProvider
     * @param array $expected
     * @param array $config
     */
    public function testMergeSettings(array $expected, array $config): void
    {
        self::assertSame($expected, Component::mergeSettings($config));
    }

    /**
     * @dataProvider iconSvgDataProvider
     * @param string $needle
     * @param string|null $icon
     * @param string $label
     */
    public function testIconSvg(string $needle, ?string $icon, string $label): void
    {
        if ($icon === null) {
            self::assertStringContainsString($needle, Cp::fallbackIconSvg($label));
        } else {
            self::assertStringContainsString($needle, Cp::iconSvg($icon, $label));
        }
    }

    /**
     * @dataProvider cleanseConfigDataProvider
     * @param array $expected
     * @param array $config
     */
    public function testCleanseConfig(array $expected, array $config)
    {
        self::assertSame($expected, Component::cleanseConfig($config));
    }

    /**
     * @return array
     */
    public static function validateComponentClassDataProvider(): array
    {
        return [
            [true, PlainText::class],
            [true, PlainText::class, FieldInterface::class],
            // fails because the class doesn't exist
            [false, 'foo\\bar\\Baz', MissingComponentException::class],
            // fails because it’s not a ComponentInterface
            [false, HorizontalRule::class, null, InvalidConfigException::class],
            [false, HorizontalRule::class, FieldLayoutElement::class, InvalidConfigException::class],
            // fails because it's the wrong interface
            [false, PlainText::class, FieldLayoutElement::class, InvalidConfigException::class],
        ];
    }

    /**
     * @return array
     */
    public static function successfulComponentCreationDataProvider(): array
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
                    /** @var DependencyHeavyComponentExample $component */
                    $component = Component::createComponent([
                        'type' => DependencyHeavyComponentExample::class,
                        'dependency1' => 'value1',
                        'dependency2' => 'value2',
                        'settings' => [
                            'settingsdependency1' => 'value',
                        ],
                    ]);

                    self::assertEquals('value1', $component->dependency1);
                    self::assertEquals('value2', $component->dependency2);
                    self::assertEquals('value', $component->settingsdependency1);
                    return $component;
                },
            ],
        ];
    }

    /**
     * Returns data for failed component creations. Defines settings, the required exception
     * and if the 'type' class must have a class as parent.
     *
     * @return array
     */
    public static function failingComponentCreationDataProvider(): array
    {
        return [
            'invalid-required-parent-class' => [
                ['type' => ExtendedComponentExample::class],
                'random\\class\\that\\doesnt\\exist',
                InvalidConfigException::class,
            ],
            'class-doesnt-exist' => [
                [
                    'type' => 'i\\dont\\exist\\as\\a\\class',
                ],
                null,
                MissingComponentException::class,
            ],
            'class-not-a-component' => [
                [
                    'type' => self::class,
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
                        'notavaliddependency3' => 'value',
                    ],
                ],
                null,
                Exception::class,
            ],

        ];
    }

    /**
     * @return array
     */
    public static function mergeSettingsDataProvider(): array
    {
        $mergedComponentArray = [
            'name' => 'Component',
            'description' => 'Lorem ipsum',
            'setting1' => 'stuff',
            'setting2' => 'stuff2',
        ];

        return [
            'json-basic' => [
                $mergedComponentArray,
                [
                    'name' => 'Component',
                    'description' => 'Lorem ipsum',
                    'settings' => json_encode([
                        'setting1' => 'stuff',
                        'setting2' => 'stuff2',
                    ]),
                ],
            ],
            'basic-component-array' => [
                $mergedComponentArray,
                [
                    'name' => 'Component',
                    'description' => 'Lorem ipsum',
                    'settings' => [
                        'setting1' => 'stuff',
                        'setting2' => 'stuff2',
                    ],
                ],
            ],
            'nested-doesnt-change' => [
                [
                    [
                        'name' => 'Component',
                        'settings' => ['setting1' => 'stuff'],
                    ],
                ],
                [
                    [
                        'name' => 'Component',
                        'settings' => ['setting1' => 'stuff'],
                    ],
                ],
            ],
            'settings-not-array' => [
                [
                    'foo' => 'bar',
                ],
                [
                    'foo' => 'bar',
                    'settings' => '"baz"',
                ],
            ],
            'key-isnt-removed' => [
                ['settings'],
                ['settings'],
            ],
            'empty-array' => [
                [],
                [],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function iconSvgDataProvider(): array
    {
        return [
            'default' => ['<title>Default</title>', null, 'Default'],
            'svg-contents' => ['<svg focusable="false" aria-hidden="true">', '<svg/>', 'Testing'],
            'svg-file' => ['<svg ', dirname(__DIR__, 2) . '/_data/assets/files/craft-logo.svg', 'Default'],
            'file-does-not-exist' => ['<title>Default</title>', '/file/does/not/exist.svg', 'Default'],
            'not-an-svg' => ['<title>Default</title>', dirname(__DIR__, 2) . '/_data/assets/files/background.jpeg', 'Default'],
            'aria-hidden' => ['aria-hidden="true"', '<svg width="100px" height="100px" viewBox="0 0 100 100" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"></svg>', 'Default'],
        ];
    }

    /**
     * @return array
     */
    public static function cleanseConfigDataProvider(): array
    {
        return [
            [
                ['f' => 'foo', 'b' => 'bar'],
                ['f' => 'foo', 'b' => 'bar', 'as f' => 'f', 'on b' => 'b'],
            ],
            [
                ['nested' => ['f' => 'foo', 'b' => 'bar']],
                ['nested' => ['f' => 'foo', 'b' => 'bar', 'as f' => 'f', 'on b' => 'b']],
            ],
        ];
    }
}
