<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Html;
use UnitTester;

/**
 * Unit tests for the HTML Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class HtmlHelperTest extends Unit
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
     * @dataProvider htmlEncodingDataProvider
     *
     * @param $result
     * @param $input
     * @param $variables
     */
    public function testParamEncoding($result, $input, $variables)
    {
        $this->assertSame($result, Html::encodeParams($input, $variables));
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function htmlEncodingDataProvider(): array
    {
        $htmlTagString = '<p>Im a paragraph. What am i, {whatIsThis}</p>';
        $pureVariableString = '{variable1}, {variable2}';
        $htmlDoubleCurlyString = '{{variable1}}, {{variable2}}';

        return [
            ['<p>Im a paragraph. What am i, A paragraph</p>', $htmlTagString, ['whatIsThis' => 'A paragraph']],
            ['stuff, other', $pureVariableString, ['variable1' => 'stuff', 'variable2' => 'other']],
            ['stuff, other', $pureVariableString, ['variable1' => 'stuff', 'variable2' => 'other']],
            ['stuff, {variable2}', $pureVariableString, ['variable1' => 'stuff']],
            'ensure-double-curly' => ['{stuff}, {{variable2}}', $htmlDoubleCurlyString, ['variable1' => 'stuff']],

            [$htmlTagString, $htmlTagString, []],
            [$pureVariableString, $pureVariableString, []],
            [
                '<p>Im a paragraph. What am i, !@#$%^&amp;*(){}|::&quot;&lt;&gt;&lt;?&gt;/*-~`</p>!@#$%^&*(){}|::"<><?>/*-~`',
                $htmlTagString . '!@#$%^&*(){}|::"<><?>/*-~`',
                ['whatIsThis' => '!@#$%^&*(){}|::"<><?>/*-~`']
            ],
            ['😘!@#$%^&amp;*(){}|::&quot;&lt;&gt;&lt;?&gt;/*-~`, {variable2}', $pureVariableString, ['variable1' => '😘!@#$%^&*(){}|::"<><?>/*-~`']]
        ];
    }
}
