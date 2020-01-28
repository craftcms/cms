<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Search;
use UnitTester;

/**
 * Unit tests for the Search Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SearchHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider keywordNormalizationDataProviders
     *
     * @param       $result
     * @param       $keyword
     * @param array $ignore
     * @param bool $processMap
     * @param null $language
     */
    public function testKeywordNormalization($result, $keyword, $ignore = [], $processMap = true, $language = null)
    {
        $keyword = Search::normalizeKeywords($keyword, $ignore, $processMap, $language);
        $this->assertSame($result, $keyword);
    }

    /**
     * @return array
     */
    public function keywordNormalizationDataProviders(): array
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
            ['♠ ♣ ♥ ♦', ['♠', '♣', '♥', '♦'], [], false],
            ['test', 'test                       '],
            ['', 'test', ['test']],
            ['🎧𢵌😀😘⛄', '🎧𢵌😀😘⛄'],

            // Ignorance isn't mb-4 safe
            ['🎧𢵌😀😘⛄', '🎧𢵌😀😘⛄', ['😀']],

            // https://github.com/craftcms/cms/issues/5214
            ['a doggs tale', 'A Dogg’s Tale'],
            ['a doggs tale', 'A Dogg\'s Tale'],
        ];
    }
}
