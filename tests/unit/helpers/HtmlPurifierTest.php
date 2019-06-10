<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\HtmlPurifier;
use HTMLPurifier_Config;

/**
 * Class HtmlPurifierTest.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class HtmlPurifierTest extends Unit
{
    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider utf8CleanDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testCleanUtf8($result, $input)
    {
        $cleaned = HtmlPurifier::cleanUtf8($input);
        $this->assertSame($result, $cleaned);
    }

    /**
     *
     */
    public function testConfigure()
    {
        $config = HTMLPurifier_Config::createDefault();
        HtmlPurifier::configure($config);
        $this->assertNull($config->get('HTML.DefinitionID'));
        $this->assertSame('', $config->get('Attr.DefaultImageAlt'));
        $this->assertSame('', $config->get('Attr.DefaultInvalidImageAlt'));
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function utf8CleanDataProvider(): array
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
