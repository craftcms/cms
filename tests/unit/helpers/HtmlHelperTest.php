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
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider encodeParamsDataProvider
     *
     * @param string $expected
     * @param string $html
     * @param array $variables
     */
    public function testEncodeParams(string $expected, string $html, array $variables)
    {
        self::assertSame($expected, Html::encodeParams($html, $variables));
    }

    /**
     * @dataProvider parseTagDataProvider
     *
     * @param array|false $expected
     * @param string $tag
     */
    public function testParseTag($expected, string $tag)
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::parseTag($tag);
        } else {
            $info = Html::parseTag($tag);
            self::assertSame($expected, [
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
     * @param string|false $expected
     * @param string $tag
     * @param string $html
     * @param string|null $ifExists
     */
    public function testAppendToTag($expected, string $tag, string $html, ?string $ifExists)
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::appendToTag($tag, $html, $ifExists);
        } else {
            self::assertSame($expected, Html::appendToTag($tag, $html, $ifExists));
        }
    }

    /**
     * @dataProvider prependToTagDataProvider
     *
     * @param string|false $expected
     * @param string $tag
     * @param string $html
     * @param string|null $ifExists
     */
    public function testPrependToTag($expected, string $tag, string $html, ?string $ifExists)
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::prependToTag($tag, $html, $ifExists);
        } else {
            self::assertSame($expected, Html::prependToTag($tag, $html, $ifExists));
        }
    }

    /**
     * @dataProvider parseTagAttributesDataProvider
     *
     * @param array|false $expected
     * @param string $tag
     */
    public function testParseTagAttributes($expected, string $tag)
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::parseTagAttributes($tag);
        } else {
            self::assertSame($expected, Html::parseTagAttributes($tag));
        }
    }

    /**
     * @dataProvider modifyTagAttributesDataProvider
     *
     * @param string|false $expected
     * @param string $tag
     * @param array $attributes
     */
    public function testModifyTagAttributes($expected, string $tag, array $attributes)
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::modifyTagAttributes($tag, $attributes);
        } else {
            self::assertSame($expected, Html::modifyTagAttributes($tag, $attributes));
        }
    }

    /**
     * @dataProvider normalizeTagAttributesDataProvider
     *
     * @param array $expected
     * @param array $attributes
     */
    public function testNormalizeTagAttributes(array $expected, array $attributes)
    {
        self::assertSame($expected, Html::normalizeTagAttributes($attributes));
    }

    /**
     * @dataProvider idDataProvider
     *
     * @param string $expected
     * @param string $id
     */
    public function testId(string $expected, string $id)
    {
        self::assertSame($expected, Html::id($id));
    }

    /**
     * @dataProvider namespaceInputNameDataProvider
     *
     * @param string $expected
     * @param string $name
     * @param string $namespace
     */
    public function testNamespaceInputName(string $expected, string $name, string $namespace)
    {
        self::assertSame($expected, Html::namespaceInputName($name, $namespace));
    }

    /**
     * @dataProvider namespaceIdDataProvider
     *
     * @param string $expected
     * @param string $name
     * @param string $namespace
     */
    public function testNamespaceId(string $expected, string $name, string $namespace)
    {
        self::assertSame($expected, Html::namespaceId($name, $namespace));
    }

    /**
     * @dataProvider namespaceInputsDataProvider
     *
     * @param string $expected
     * @param string $html
     * @param string $namespace
     */
    public function testNamespaceInputs(string $expected, string $html, string $namespace)
    {
        self::assertSame($expected, Html::namespaceInputs($html, $namespace));
    }

    /**
     * @dataProvider namespaceAttributesDataProvider
     *
     * @param string $expected
     * @param string $html
     * @param string $namespace
     * @param bool $classNames
     */
    public function testNamespaceAttributes(string $expected, string $html, string $namespace, bool $classNames)
    {
        self::assertSame($expected, Html::namespaceAttributes($html, $namespace, $classNames));
    }

    /**
     * @return array
     */
    public function encodeParamsDataProvider(): array
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
            [['data' => ['foo' => true]], '<div data-foo>'],
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
            ['<!-- comment --> <input type="text" />', '<!-- comment --> <input type="text" disabled />', ['disabled' => false]],
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
            // https://github.com/craftcms/cms/issues/6973
            ['<custom-element class="foo"></custom-element>', '<custom-element></custom-element>', ['class' => 'foo']],
            // https://github.com/craftcms/cms/issues/7234
            ['<div>', '<div class="foo">', ['class' => false]],
            ['<div>', '<div style="background: red">', ['style' => false]],
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
            [['data' => ['foo' => true]], ['data-foo' => true]],
            // https://github.com/craftcms/cms/issues/7234
            [['class' => false], ['class' => false]],
            [['class' => false], ['class' => null]],
            [['class' => false], ['class' => false]],
            [['class' => false], ['class' => null]],
        ];
    }

    /**
     * @return array
     */
    public function idDataProvider(): array
    {
        return [
            ['foo', '-foo-'],
            ['foo-bar', 'foo--bar'],
            ['foo-bar-baz', 'foo[bar][baz]'],
            ['foo-bar-baz', 'foo bar baz'],
        ];
    }

    /**
     * @return array
     */
    public function namespaceInputNameDataProvider(): array
    {
        return [
            ['foo[bar]', 'bar', 'foo'],
            ['foo[bar][baz]', 'bar[baz]', 'foo'],
        ];
    }

    /**
     * @return array
     */
    public function namespaceIdDataProvider(): array
    {
        return [
            ['foo-bar', 'bar', 'foo'],
            ['foo-bar-baz', 'bar[baz]', 'foo'],
            ['foo-bar-baz', 'baz', 'foo[bar]'],
        ];
    }

    /**
     * @return array
     */
    public function namespaceInputsDataProvider(): array
    {
        return [
            ['<input name="foo[bar]">', '<input name="bar">', 'foo'],
            ['<input name="foo[bar][baz]">', '<input name="bar[baz]">', 'foo'],
            ['<textarea name="foo[bar]"><input name="foo"></textarea>', '<textarea name="bar"><input name="foo"></textarea>', 'foo'],
        ];
    }

    /**
     * @return array
     */
    public function namespaceAttributesDataProvider(): array
    {
        return [
            ['<div id="foo-bar"></div>', '<div id="bar"></div>', 'foo', false],
            ['<textarea><div id="foo"></textarea>', '<textarea><div id="foo"></textarea>', 'foo', false],
            ['<div id="foo-bar"></div><div for="foo-bar">', '<div id="bar"></div><div for="bar">', 'foo', false],
            ['<div id="foo-bar-baz"></div><div for="foo-bar-baz">', '<div id="bar-baz"></div><div for="bar-baz">', 'foo', false],
            ['<div for="bar">', '<div for="bar">', 'foo', false],
            ['<div id="foo-bar"></div><div list="foo-bar">', '<div id="bar"></div><div list="bar">', 'foo', false],
            ['<div id="foo-bar"></div><div aria-labelledby="foo-bar">', '<div id="bar"></div><div aria-labelledby="bar">', 'foo', false],
            ['<div id="foo-bar"></div><div aria-describedby="foo-bar">', '<div id="bar"></div><div aria-describedby="bar">', 'foo', false],
            ['<div id="foo-bar"></div><div data-target="foo-bar">', '<div id="bar"></div><div data-target="bar">', 'foo', false],
            ['<div id="foo-bar"></div><div data-target="#foo-bar">', '<div id="bar"></div><div data-target="#bar">', 'foo', false],
            ['<div id="foo-bar"></div><div data-reverse-target="foo-bar">', '<div id="bar"></div><div data-reverse-target="bar">', 'foo', false],
            ['<div id="foo-bar"></div><div data-reverse-target="#foo-bar">', '<div id="bar"></div><div data-reverse-target="#bar">', 'foo', false],
            ['<div id="foo-bar-baz"></div><div data-target-prefix="foo-bar-">', '<div id="bar-baz"></div><div data-target-prefix="bar-">', 'foo', false],
            ['<div id="foo-bar-baz"></div><div data-target-prefix=".bar-">', '<div id="bar-baz"></div><div data-target-prefix=".bar-">', 'foo', false],
            ['<div class="foo bar">', '<div class="foo bar">', 'foo', false],
            ['<div class="foo-bar foo-baz">', '<div class="bar baz">', 'foo', true],
            ['<div class="foo-bar-baz">', '<div class="bar-baz">', 'foo', true],
            ['<div id="foo-bar"></div>#foo', '<div id="bar"></div>#foo', 'foo', false],
            ['<div id="foo-bar"></div>.foo', '<div id="bar"></div>.foo', 'foo', false],
            ['<div id="foo-bar"></div>.foo', '<div id="bar"></div>.foo', 'foo', true],
            ['<style>#bar{}</style>', '<style>#bar{}</style>', 'foo', false],
            ['<div id="foo-bar"></div><style>#foo-bar{}</style>', '<div id="bar"></div><style>#bar{}</style>', 'foo', false],
            ['<style>.foo{}</style>', '<style>.foo{}</style>', 'foo', false],
            ['<style>.foo-bar{}</style>', '<style>.bar{}</style>', 'foo', true],
            ['<style>.foo-bar{content: \'.baz\'}</style>', '<style>.bar{content: \'.baz\'}</style>', 'foo', true],
            ['<linearGradient id="foo-bar"></linearGradient><path fill="url(#foo-bar)"></path>', '<linearGradient id="bar"></linearGradient><path fill="url(#bar)"></path>', 'foo', false],
            ['<style>.foo-st4{mask:url(#foo-bar);fill-rule:evenodd;fill:url(#foo-bla);}</style><mask id="foo-bar"></mask><linearGradient id="foo-bla"></linearGradient>', '<style>.st4{mask:url(#bar);fill-rule:evenodd;fill:url(#bla);}</style><mask id="bar"></mask><linearGradient id="bla"></linearGradient>', 'foo', true],
            ['<circle id="foo-bar"></circle><use xlink:href="#foo-bar"></use>', '<circle id="bar"></circle><use xlink:href="#bar"></use>', 'foo', false],
        ];
    }
}
