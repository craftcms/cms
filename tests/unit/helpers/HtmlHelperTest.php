<?php
/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 28/09/2018
 * Time: 11:35
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

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testParamEncoding()
    {
        $htmlTagString = '<p>Im a paragraph. What am i, {whatIsThis}</p>';
        $pureVariableString = '{variable1}, {variable2}';
        $htmlDoubleCurlyString = '{{variable1}}, {{variable2}}';

        $this->assertSame(
            Html::encodeParams($htmlTagString, ['whatIsThis' => 'A paragraph']),
            '<p>Im a paragraph. What am i, A paragraph</p>'
        );

        $this->assertSame(
            Html::encodeParams($pureVariableString, ['variable1' => 'stuff', 'variable2' => 'other']),
            'stuff, other'
        );

        $this->assertSame(
            Html::encodeParams($pureVariableString, ['variable1' => 'stuff', 'variable2' => 'other']),
            'stuff, other'
        );

        // Ensure with partial matches it only encodes what is present.
        $this->assertSame(
            Html::encodeParams($pureVariableString, ['variable1' => 'stuff']),
            'stuff, {variable2}'
        );

        // Ensure on double curly that it encodes and leaves the second curly brace.
        $this->assertSame(
            Html::encodeParams($htmlDoubleCurlyString, ['variable1' => 'stuff']),
            '{stuff}, {{variable2}}'
        );

        // Empty param testing.
        $this->assertSame(
            Html::encodeParams($htmlTagString, []),
            $htmlTagString
        );
        $this->assertSame(
            Html::encodeParams($pureVariableString, []),
            $pureVariableString
        );

        $this->assertSame(
            '<p>Im a paragraph. What am i, !@#$%^&amp;*(){}|::&quot;&lt;&gt;&lt;?&gt;/*-~`</p>!@#$%^&*(){}|::"<><?>/*-~`',
            Html::encodeParams($htmlTagString.'!@#$%^&*(){}|::"<><?>/*-~`', ['whatIsThis' => '!@#$%^&*(){}|::"<><?>/*-~`'])
        );

        // Ensure on double curly that it encodes and leaves the second curly brace.
        $this->assertSame(
            '😘!@#$%^&amp;*(){}|::&quot;&lt;&gt;&lt;?&gt;/*-~`, {variable2}',
            Html::encodeParams($pureVariableString, ['variable1' => '😘!@#$%^&*(){}|::"<><?>/*-~`'])
        );
    }
}