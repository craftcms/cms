<?php
/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 28/09/2018
 * Time: 11:35
 */

namespace app\helpers;


use craft\helpers\Html;

class HtmlHelperTest extends \Codeception\Test\Unit
{
    public function testParamEncoding()
    {
        $htmlString1 = '<p>Im a paragraph. What am i, {{whatIsThis}}</p>';
        $htmlString2 = '{{variable1}}, {{variable2}}';

        $this->assertSame(
            Html::encodeParams($htmlString1, ['whatIsThis' => 'A paragraph']),
            '<p>Im a paragraph. What am i, A paragraph</p>'
        );

        $this->assertSame(
            Html::encodeParams($htmlString2, ['variable1' => 'stuff', 'variable2' => 'other']),
            'stuff, other'
        );

        // TODO: More tests needed here. for empty and invalid params e.t.c.
    }
}