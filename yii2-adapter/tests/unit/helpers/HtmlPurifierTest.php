<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\HtmlPurifier;
use craft\test\TestCase;
use CraftCms\Cms\Support\Facades\Deprecator;
use HTMLPurifier_Config;

/**
 * Class HtmlPurifierTest.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class HtmlPurifierTest extends TestCase
{
    /**
     * @dataProvider cleanUtf8DataProvider
     * @param string $expected
     * @param string $string
     */
    public function testCleanUtf8(string $expected, string $string): void
    {
        self::assertSame($expected, HtmlPurifier::cleanUtf8($string));
    }

    /**
     *
     */
    public function testConfigure(): void
    {
        $config = HTMLPurifier_Config::createDefault();
        HtmlPurifier::configure($config);
        self::assertNull($config->get('HTML.DefinitionID'));
        self::assertSame('', $config->get('Attr.DefaultImageAlt'));
        self::assertSame('', $config->get('Attr.DefaultInvalidImageAlt'));
    }

    public function testProcessWithForwardableConfig(): void
    {
        Deprecator::deleteAllLogs();

        $html = HtmlPurifier::process('<p id="test" bad-attr="bad">Hello</p>', [
            'Attr.EnableID' => true,
        ]);

        self::assertSame('<p id="test">Hello</p>', $html);
        self::assertNotEmpty(Deprecator::getRequestLogs());
    }

    public function testConvertToUtf8(): void
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'iso-8859-1');
        $string = iconv('UTF-8', 'ISO-8859-1//IGNORE', 'Café');

        self::assertSame('Café', HtmlPurifier::convertToUtf8($string, $config));
    }

    /**
     * @return array
     */
    public static function cleanUtf8DataProvider(): array
    {
        // https://github.com/ezyang/htmlpurifier/blob/master/tests/HTMLPurifier/EncoderTest.php#L21
        return [
            ['test', 'test'],
            ['null byte: ', "null byte: \0"],
            ['あ（い）う（え）お', "あ（い）う（え）お\0"],
            ['', "\1\2\3\4\5\6\7"],
            ['', "\x7F"],
            ['', "\xC2\x80"],
            ['', "\xDF\xFF"],
            ["\xF3\xBF\xBF\xBF", "\xF3\xBF\xBF\xBF"],
            ['', "\xED\xB0\x80"],
            ['😀😘', '😀😘'],
        ];
    }
}
