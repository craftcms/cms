<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Search;
use craft\test\TestCase;

/**
 * Unit tests for the Search Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SearchHelperTest extends TestCase
{
    /**
     * @dataProvider normalizeKeywordsDataProviders
     * @param string $expected
     * @param string|string[] $str
     * @param array $ignore
     * @param bool $processCharMap
     * @param string|null $language
     */
    public function testNormalizeKeywords(string $expected, array|string $str, array $ignore = [], bool $processCharMap = true, ?string $language = null): void
    {
        self::assertSame($expected, Search::normalizeKeywords($str, $ignore, $processCharMap, $language));
    }

    /**
     * @return array
     */
    public static function normalizeKeywordsDataProviders(): array
    {
        return [
            ['test', 'test'],
            ['test test', ['test', 'test']],
            ['test test', 'test <?php echo "test"; ?>test'],
            ['test test', ['<div class="test"><a download>test </a>test</div>']],
            ['', ['&nbsp;', '&#160;', '&#xa0;']],
            ['test', 'test &#160;  '],
            ['', '&#--++;'],
            ['', '&#11aa;'],
            ['test test', 'TEST TEST'],
            ['', ['♠', '♣', '♥', '♦']],
            ['', ['♠', '♣', '♥', '♦'], [], false],
            ['test', 'test                       '],
            ['', 'test', ['test']],
            ['test', 'test👍'],
            // https://github.com/craftcms/cms/issues/5214
            ['a doggs tale', 'A Dogg’s Tale'],
            ['a doggs tale', 'A Dogg\'s Tale'],
            // https://github.com/craftcms/cms/issues/5631
            ['foo bar baz', '<p>Foo</p><p>Bar<br>Baz</p>'],
            // https://github.com/craftcms/cms/issues/12467
            ['bienvenue de espace', "Bienvenue de l'espace"],
            ['bienvenue de espace', 'Bienvenue de l’espace'],
            ['this accord', 'this?accord!'],
            ['this accord', "this?D'accord!"],
            ['this accord', 'this?D’accord!'],
            ['womens', "women's"],
            ['womens', 'women’s'],
        ];
    }
}
