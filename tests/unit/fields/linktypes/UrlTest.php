<?php

namespace crafttests\unit\fields\linktypes;

use craft\fields\linktypes\Url;
use craft\test\TestCase;

class UrlTest extends TestCase
{
    /**
     * @dataProvider validateValueDataProvider
     * @return void
     */
    public function testValidateValue(string $value, bool $expected, array $config = [])
    {
        $error = null;
        $urlField = new Url($config);

        $result = $urlField->validateValue($value, $error);

        self::assertEquals($expected, $result);
    }

    public static function validateValueDataProvider(): array
    {
        return [
            ['https://google.com', true],
            ['https://münchen-ost.com', true],
            ['https://www.münchen-ost.com', true],
            ['/some-relative-url', true, [
                'allowRootRelativeUrls' => true,
            ]],
            ['/some-relative-url', false, [
                'allowRootRelativeUrls' => false,
            ]],
            ['#anchor', false, [
                'allowAnchors' => false,
            ]],
            ['#anchor', true, [
                'allowAnchors' => true,
            ]],
        ];
    }
}
