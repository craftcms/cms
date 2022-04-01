<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\i18n;

use Codeception\Test\Unit;
use Craft;
use craft\test\TestCase;

/**
 * Unit tests for the Formatter class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FormatterTest extends TestCase
{
    /**
     * @param string $expected
     * @param mixed $value
     * @param int|null $decimals
     * @dataProvider asPercentDataProvider
     */
    public function testAsPercent(string $expected, mixed $value, ?int $decimals = null): void
    {
        self::assertSame($expected, Craft::$app->getFormatter()->asPercent($value, $decimals));
    }

    /**
     * @return array[]
     */
    public function asPercentDataProvider(): array
    {
        return [
            ['0%', null],
            ['0%', 0],
            ['10%', 0.1],
            ['10.5%', 0.105],
            ['10.50%', 0.105, 2],
        ];
    }
}
