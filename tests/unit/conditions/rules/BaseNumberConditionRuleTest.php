<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\conditions\rules;

use Codeception\Test\Unit;
use craft\base\conditions\BaseNumberConditionRule;
use craft\test\TestCase;

/**
 * Unit tests for BaseNumberConditionRule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class BaseNumberConditionRuleTest extends TestCase
{
    /**
     * @param array $config
     * @param array|null $expected
     * @return void
     * @dataProvider setAttributesDataProvider
     */
    public function testSetAttributes(array $config, ?array $expected = null): void
    {
        $expected = $expected ?? $config;

        $rule = new class() extends BaseNumberConditionRule {
            public function getLabel(): string
            {
                return 'TestNumberConditionRule';
            }
        };

        $rule->setAttributes($config);

        foreach ($expected as $attribute => $value) {
            self::assertEquals($value, $rule->$attribute);
        }
    }

    public static function setAttributesDataProvider(): array
    {
        return [
            [
                ['operator' => 'between', 'value' => '1', 'maxValue' => '2'],
            ],
            [
                ['operator' => '=', 'value' => '1'],
            ],
        ];
    }

    /**
     * @param array $config
     * @param string|null $expected
     * @return void
     * @dataProvider paramValueDataProvider
     */
    public function testParamValue(array $config, ?string $expected): void
    {
        $rule = new class() extends BaseNumberConditionRule {
            public function getLabel(): string
            {
                return 'TestNumberConditionRule';
            }

            public function testParamValue(): ?string
            {
                // Shortcut for testing `paramValue()` return
                return $this->paramValue();
            }
        };

        $rule->setAttributes($config);

        self::assertEquals($expected, $rule->testParamValue());
    }

    /**
     * @return array
     */
    public static function paramValueDataProvider(): array
    {
        return [
            [
                ['operator' => '=', 'value' => 1],
                '= 1',
            ],
            [
                ['operator' => '!=', 'value' => 1],
                '!= 1',
            ],
            [
                ['operator' => '<', 'value' => 1],
                '< 1',
            ],
            [
                ['operator' => '<=', 'value' => 1],
                '<= 1',
            ],
            [
                ['operator' => '>', 'value' => 1],
                '> 1',
            ],
            [
                ['operator' => '>=', 'value' => 1],
                '>= 1',
            ],
            [
                ['operator' => 'between', 'value' => 1, 'maxValue' => 2],
                'and, >= 1, <= 2',
            ],
            [
                ['operator' => 'between', 'value' => 1],
                '>= 1',
            ],
            [
                ['operator' => 'between', 'maxValue' => 2],
                '<= 2',
            ],
            [
                ['operator' => 'between'],
                null,
            ],
        ];
    }
}
