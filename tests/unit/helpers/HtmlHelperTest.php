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
use yii\base\InvalidArgumentException;

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

    /**
     * @dataProvider parseTagDataProvider
     *
     * @param $result
     * @param $tag
     */
    public function testParseTag($result, $tag)
    {
        if ($result === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::parseTag($tag);
        } else {
            $info = Html::parseTag($tag);
            $this->assertSame($result, [
                $info['type'],
                $info['attributes'],
                isset($info['htmlStart'], $info['htmlEnd'])
                    ? substr($tag, $info['htmlStart'], $info['htmlEnd'] - $info['htmlStart'])
                    : null
            ]);
        }
    }

    /**
     * @dataProvider appendToTagDataProvider
     *
     * @param $result
     * @param $tag
     * @param $html
     * @param $ifExists
     */
    public function testAppendToTag($result, $tag, $html, $ifExists)
    {
        if ($result === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::appendToTag($tag, $html, $ifExists);
        } else {
            $this->assertSame($result, Html::appendToTag($tag, $html, $ifExists));
        }
    }

    /**
     * @dataProvider prependToTagDataProvider
     *
     * @param $result
     * @param $tag
     * @param $html
     * @param $ifExists
     */
    public function testPrependToTag($result, $tag, $html, $ifExists)
    {
        if ($result === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::prependToTag($tag, $html, $ifExists);
        } else {
            $this->assertSame($result, Html::prependToTag($tag, $html, $ifExists));
        }
    }

    /**
     * @dataProvider parseTagAttributesDataProvider
     *
     * @param $result
     * @param $tag
     */
    public function testParseTagAttributes($result, $tag)
    {
        if ($result === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::parseTagAttributes($tag);
        } else {
            $this->assertSame($result, Html::parseTagAttributes($tag));
        }
    }

    /**
     * @dataProvider modifyTagAttributesDataProvider
     *
     * @param $result
     * @param $tag
     * @param $attributes
     */
    public function testModifyTagAttributes($result, $tag, $attributes)
    {
        if ($result === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::modifyTagAttributes($tag, $attributes);
        } else {
            $this->assertSame($result, Html::modifyTagAttributes($tag, $attributes));
        }
    }

    /**
     * @dataProvider normalizeTagAttributesDataProvider
     *
     * @param $result
     * @param $attributes
     */
    public function testNormalizeTagAttributes($result, $attributes)
    {
        $this->assertSame($result, Html::normalizeTagAttributes($attributes));
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

    /**
     * @return array
     */
    public function parseTagDataProvider(): array
    {
        return [
            [['p', ['class' => ['foo']], 'Hello<br>there'], '<p class="foo">Hello<br>there</p>'],
            [['div', [], '<div>Nested</div>'], '<div><div>Nested</div></div>'],
            [['br', [], null], '<br>'],
            [['br', [], null], '<br />'],
            [['div', [], null], '<div />'],
            [false, '<div>'],
        ];
    }

    /**
     * @return array
     */
    public function appendToTagDataProvider(): array
    {
        return [
            ['<div><p>Foo</p><p>Bar</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', null],
            ['<div><p>Foo</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'keep'],
            ['<div><p>Bar</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'replace'],
            [false, '<div />', '<p>Bar</p>', null],
            [false, '<div><p>Foo</p></div>', 'Bar', 'keep'],
        ];
    }

    /**
     * @return array
     */
    public function prependToTagDataProvider(): array
    {
        return [
            ['<div><p>Foo</p><p>Bar</p></div>', '<div><p>Bar</p></div>', '<p>Foo</p>', null],
            ['<div><p>Foo</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'keep'],
            ['<div><p>Bar</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'replace'],
            [false, '<div />', '<p>Bar</p>', null],
            [false, '<div><p>Foo</p></div>', 'Bar', 'keep'],
        ];
    }

    /**
     * @return array
     */
    public function parseTagAttributesDataProvider(): array
    {
        return [
            [['type' => 'text', 'disabled' => true], '<input type="text" disabled>'],
            [['type' => 'text', 'disabled' => true], '<input type=text disabled />'],
            [['type' => 'text'], '<!-- comment --> <input type="text">'],
            [['type' => 'text'], '<?xml?> <input type="text">'],
            [['data' => ['foo' => '1', 'bar' => '2']], '<div data-foo="1" data-bar="2">'],
            [['data-ng' => ['foo' => '1', 'bar' => '2']], '<div data-ng-foo="1" data-ng-bar="2">'],
            [['ng' => ['foo' => '1', 'bar' => '2']], '<div ng-foo="1" ng-bar="2">'],
            [['data-foo' => true], '<div data-foo>'],
            [['class' => ['foo', 'bar']], '<div class="foo bar">'],
            [['style' => ['color' => 'black', 'background' => 'red']], '<div style="color: black; background: red">'],
            [false, '<div'],
            [false, '<!-- comment -->'],
            [false, '<?xml?>'],
        ];
    }

    /**
     * @return array
     */
    public function modifyTagAttributesDataProvider(): array
    {
        return [
            ['<input type="text">', '<input type="text" disabled>', ['disabled' => false]],
            [ '<!-- comment --> <input type="text" />',  '<!-- comment --> <input type="text" disabled />', ['disabled' => false]],
            ['<div class="foo bar">', '<div class="foo">', ['class' => ['foo', 'bar']]],
            ['<div data-foo="2" data-bar="3">', '<div data-foo="1">', ['data' => ['foo' => '2', 'bar' => '3']]],
            ['<div style="color: black; background: red;">', '<div>', ['style' => ['color' => 'black', 'background' => 'red']]],
            ['<div style="color: black; background: red;">', '<div style="color: red">', ['style' => ['color' => 'black', 'background' => 'red']]],
            [false, '<div', []],
            [false, '<!-- comment -->', []],
            [false, '<?xml?>', []],
            // https://github.com/craftcms/cms/issues/4984
            ['<img class="foo" src="image.jpg?width=100&amp;height=100">', '<img src="image.jpg?width=100&height=100">', ['class' => 'foo']],
            ['<img class="foo" src="image.jpg?width=100&amp;height=100">', '<img src="image.jpg?width=100&amp;height=100">', ['class' => 'foo']],
        ];
    }

    /**
     * @return array
     */
    public function normalizeTagAttributesDataProvider(): array
    {
        return [
            [['type' => 'text', 'disabled' => true], ['type' => 'text', 'disabled' => true]],
            [['class' => ['foo', 'bar']], ['class' => 'foo bar']],
            [['style' => ['color' => 'black', 'background' => 'red']], ['style' => 'color: black; background: red;']],
            [['data' => ['foo' => '1', 'bar' => '2']], ['data-foo' => '1', 'data-bar' => '2']],
            [['data-ng' => ['foo' => '1', 'bar' => '2']], ['data-ng-foo' => '1', 'data-ng-bar' => '2']],
            [['ng' => ['foo' => '1', 'bar' => '2']], ['ng-foo' => '1', 'ng-bar' => '2']],
            [['data-foo' => true], ['data-foo' => true]],
        ];
    }
}
