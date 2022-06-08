<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Json;
use craft\test\TestCase;

/**
 * Unit tests for the Json Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class JsonHelperTest extends TestCase
{
    /**
     * @dataProvider decodeIfJsonDataProvider
     * @param mixed $expected
     * @param string $str
     */
    public function testDecodeIfJson(mixed $expected, string $str): void
    {
        self::assertSame($expected, Json::decodeIfJson($str));
    }

    public function decodeIfJsonDataProvider(): array
    {
        $basicArray = [
            'WHAT DO WE WANT' => 'JSON',
            'WHEN DO WE WANT IT' => 'NOW',
        ];
        return [
            ['{"test":"test"', '{"test":"test"'],
            [$basicArray, json_encode($basicArray)],
            [null, ''],
        ];
    }

    /**
     * @dataProvider isJsonObjectDataProvider
     * @param bool $expected
     * @param string $str
     */
    public function testIsJsonObject(bool $expected, string $str): void
    {
        self::assertSame($expected, Json::isJsonObject($str));
    }

    public function isJsonObjectDataProvider(): array
    {
        return [
            [true, '{"foo":true}'],
            [true, "{\n  \"foo\": true\n}"],
            [false, '{"foo":true'],
        ];
    }
}
