<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;


use craft\helpers\Html;

/**
 * Unit tests for the HTML Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class HtmlHelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

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

    public function htmlEncodingDataProvider()
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
                $htmlTagString.'!@#$%^&*(){}|::"<><?>/*-~`',
                ['whatIsThis' => '!@#$%^&*(){}|::"<><?>/*-~`']
            ],
            ['😘!@#$%^&amp;*(){}|::&quot;&lt;&gt;&lt;?&gt;/*-~`, {variable2}', $pureVariableString, ['variable1' => '😘!@#$%^&*(){}|::"<><?>/*-~`']]
        ];
    }
}