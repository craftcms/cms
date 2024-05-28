<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Html;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for the HTML Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class HtmlHelperTest extends TestCase
{
    /**
     * @dataProvider encodeParamsDataProvider
     * @param string $expected
     * @param string $html
     * @param array $variables
     */
    public function testEncodeParams(string $expected, string $html, array $variables): void
    {
        self::assertSame($expected, Html::encodeParams($html, $variables));
    }

    /**
     * @dataProvider encodeSpacesDataProvider
     * @param string $expected
     * @param string $str
     */
    public function testEncodeSpaces(string $expected, string $str): void
    {
        self::assertSame($expected, Html::encodeSpaces($str));
    }

    /**
     * @dataProvider parseTagDataProvider
     * @param array|false $expected
     * @param string $tag
     */
    public function testParseTag(array|false $expected, string $tag): void
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            Html::parseTag($tag);
        } else {
            self::assertSame($expected, $this->_normalizeParseTagInfo(Html::parseTag($tag)));
        }
    }

    private function _normalizeParseTagInfo(array $info): array
    {
        if ($info['type'] === 'text') {
            return ['text', $info['value']];
        }

        return [
            $info['type'],
            $info['attributes'],
            array_map([$this, '_normalizeParseTagInfo'], $info['children']),
        ];
    }

    /**
     * @dataProvider appendToTagDataProvider
     * @param string|false $expected
     * @param string $tag
     * @param string $html
     * @param string|null $ifExists
     */
    public function testAppendToTag(string|false $expected, string $tag, string $html, ?string $ifExists): void
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
     * @param string|false $expected
     * @param string $tag
     * @param string $html
     * @param string|null $ifExists
     */
    public function testPrependToTag(string|false $expected, string $tag, string $html, ?string $ifExists): void
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
     * @param array|false $expected
     * @param string $tag
     */
    public function testParseTagAttributes(array|false $expected, string $tag): void
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
     * @param string|false $expected
     * @param string $tag
     * @param array $attributes
     */
    public function testModifyTagAttributes(string|false $expected, string $tag, array $attributes): void
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
     * @param array $expected
     * @param array $attributes
     */
    public function testNormalizeTagAttributes(array $expected, array $attributes): void
    {
        self::assertSame($expected, Html::normalizeTagAttributes($attributes));
    }

    /**
     * @dataProvider idDataProvider
     * @param string|null $expected
     * @param string $id
     */
    public function testId(?string $expected, string $id): void
    {
        if ($expected) {
            self::assertSame($expected, Html::id($id));
        } else {
            self::assertEquals(10, strlen(Html::id($id)));
        }
    }

    /**
     * @dataProvider namespaceInputNameDataProvider
     * @param string $expected
     * @param string $name
     * @param string|null $namespace
     */
    public function testNamespaceInputName(string $expected, string $name, ?string $namespace): void
    {
        self::assertSame($expected, Html::namespaceInputName($name, $namespace));
    }

    /**
     * @dataProvider namespaceIdDataProvider
     * @param string $expected
     * @param string $name
     * @param string|null $namespace
     */
    public function testNamespaceId(string $expected, string $name, ?string $namespace): void
    {
        self::assertSame($expected, Html::namespaceId($name, $namespace));
    }

    /**
     * @dataProvider namespaceInputsDataProvider
     * @param string $expected
     * @param string $html
     * @param string $namespace
     */
    public function testNamespaceInputs(string $expected, string $html, string $namespace): void
    {
        self::assertSame($expected, Html::namespaceInputs($html, $namespace));
    }

    /**
     * @dataProvider namespaceAttributesDataProvider
     * @param string $expected
     * @param string $html
     * @param string $namespace
     * @param bool $classNames
     */
    public function testNamespaceAttributes(string $expected, string $html, string $namespace, bool $classNames): void
    {
        self::assertSame($expected, Html::namespaceAttributes($html, $namespace, $classNames));
    }

    /**
     * @dataProvider widontDataProvider
     * @param string $expected
     * @param string $string
     */
    public function testWidont(string $expected, string $string): void
    {
        self::assertSame($expected, Html::widont($string));
    }

    /**
     * @dataProvider encodeInvalidTagsDataProvider
     * @param string $expected
     * @param string $html
     */
    public function testEncodeInvalidTags(string $expected, string $html): void
    {
        self::assertSame($expected, Html::encodeInvalidTags($html));
    }

    /**
     *
     */
    public function testUnwrapCondition(): void
    {
        // No condition
        $jsFile = Html::jsFile('foo.js');
        self::assertSame([$jsFile, null], Html::unwrapCondition($jsFile));

        // Positive condition
        $condition = 'lt IE 9';
        $conditionalJsFile = Html::jsFile('foo.js', ['condition' => $condition]);
        self::assertSame([$jsFile, $condition], Html::unwrapCondition($conditionalJsFile));

        // Negative condition
        $condition = '!IE 9';
        $conditionalJsFile = Html::jsFile('foo.js', ['condition' => $condition]);
        self::assertSame([$jsFile, $condition], Html::unwrapCondition($conditionalJsFile));

        // Content with newlines
        $condition = 'lt IE 9';
        $content = "foo\nbar\nbaz";
        $conditionalContent = str_replace($jsFile, $content, Html::jsFile('foo.js', ['condition' => $condition]));
        self::assertSame([$content, $condition], Html::unwrapCondition($conditionalContent));
    }

    /**
     *
     */
    public function testUnwrapNoscript(): void
    {
        // Without <noscript>>
        $cssFile = Html::cssFile('foo.css');
        self::assertSame([$cssFile, false], Html::unwrapNoscript($cssFile));

        // With <noscript>
        $noscriptCssFile = Html::cssFile('foo.css', ['noscript' => true]);
        self::assertSame([$cssFile, true], Html::unwrapNoscript($noscriptCssFile));

        // Content with newlines
        $content = "foo\nbar\nbaz";
        $noscriptContent = str_replace($cssFile, $content, Html::cssFile('foo.css', ['noscript' => true]));
        self::assertSame([$content, true], Html::unwrapNoscript($noscriptContent));
    }

    /**
     *
     */
    public function testSvg(): void
    {
        $path = dirname(__DIR__, 2) . '/_data/assets/files/craft-logo.svg';
        $contents = file_get_contents($path);

        $svg = Html::svg($path);
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('id="Symbols"', $svg);

        $svg = Html::svg($contents);
        self::assertStringStartsWith('<svg', $svg);
        self::assertRegExp('/id="\w+\-Symbols"/', $svg);

        $svg = Html::svg($contents, namespace: false);
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('id="Symbols"', $svg);
    }

    /**
     * @return array
     */
    public static function encodeParamsDataProvider(): array
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
                ['whatIsThis' => '!@#$%^&*(){}|::"<><?>/*-~`'],
            ],
            ['😘!@#$%^&amp;*(){}|::&quot;&lt;&gt;&lt;?&gt;/*-~`, {variable2}', $pureVariableString, ['variable1' => '😘!@#$%^&*(){}|::"<><?>/*-~`']],
        ];
    }

    /**
     * @return array
     */
    public static function encodeSpacesDataProvider(): array
    {
        return [
            ['foo%20bar', 'foo bar'],
            ['foo%20%20bar', 'foo  bar'],
        ];
    }

    /**
     * @return array
     */
    public static function parseTagDataProvider(): array
    {
        return [
            [
                [
                    'p', ['class' => ['foo']], [
                    ['text', 'Hello'],
                    ['br', [], []],
                    ['text', 'there'],
                ],
                ], '<p class="foo">Hello<br>there</p>',
            ],
            [
                [
                    'div', [], [
                    ['div', [], [['text', 'Nested']]],
                ],
                ], '<div><div>Nested</div></div>',
            ],
            [['br', [], []], '<br>'],
            [['br', [], []], '<br />'],
            [['div', [], []], '<div />'],
            [
                [
                    'script', ['type' => 'text/javascript'], [
                    ['text', "var \$p = $('<p>Hello</p>');\n"],
                ],
                ], "<script type=\"text/javascript\">var \$p = $('<p>Hello</p>');\n</script>",
            ],
            [false, '<div>'],
        ];
    }

    /**
     * @return array
     */
    public static function appendToTagDataProvider(): array
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
    public static function prependToTagDataProvider(): array
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
    public static function parseTagAttributesDataProvider(): array
    {
        return [
            [[], '<div/>'],
            [['x-foo' => true], '<div x-foo=>'],
            [['x-foo' => true], '<div x-foo="">'],
            [['x-foo' => true], "<div x-foo=''>"],
            [['type' => 'text', 'disabled' => true], '<input type="text" disabled>'],
            [['type' => 'text', 'disabled' => true], '<input type=text disabled />'],
            [['type' => 'text'], '<!-- comment --> <input type="text">'],
            [['type' => 'text'], '<?xml?> <input type="text">'],
            [['type' => 'text'], "<input type='text'>"],
            [['type' => 'text'], '<input type=text>'],
            [['type' => 'text'], '<input type = "text">'],
            [['type' => 'text'], "<input type = 'text'>"],
            [['type' => 'text'], '<input type = text>'],
            [['type' => 'text'], "<input type = text\n>"],
            [['x-foo' => '<bar>'], '<div x-foo="<bar>">'],
            [['x-foo' => '"<bar>"'], "<div x-foo='\"<bar>\"'>"],
            [['data' => ['foo' => '1', 'bar' => '2']], '<div data-foo="1" data-bar="2">'],
            [['data-ng' => ['foo' => '1', 'bar' => '2']], '<div data-ng-foo="1" data-ng-bar="2">'],
            [['ng' => ['foo' => '1', 'bar' => '2']], '<div ng-foo="1" ng-bar="2">'],
            [['data' => ['foo' => true]], '<div data-foo>'],
            [['class' => ['foo', 'bar']], '<div class="foo bar">'],
            [['style' => ['color' => 'black', 'background' => 'red']], '<div style="color: black; background: red">'],
            // https://github.com/craftcms/cms/issues/12887
            [['class' => ['[&[disabled]]:opacity-50']], '<button class="[&amp;[disabled]]:opacity-50"></button>'],
            [false, '<div'],
            [false, '<div x-foo=">'],
            [false, "<div x-foo='>"],
            [false, '<!-- comment -->'],
            [false, '<?xml?>'],
            // https://github.com/craftcms/cms/issues/14498
            [['data' => ['label' => "foo\n\nbar"]], "<div data-label=\"foo\n\nbar\">"],
        ];
    }

    /**
     * @return array
     */
    public static function modifyTagAttributesDataProvider(): array
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
            // https://github.com/craftcms/cms/issues/12887
            ['<button class="[&amp;[disabled]]:opacity-50" disabled></button>', '<button class="[&amp;[disabled]]:opacity-50"></button>', ['disabled' => true]],
        ];
    }

    /**
     * @return array
     */
    public static function normalizeTagAttributesDataProvider(): array
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
            // https://github.com/craftcms/cms/issues/14964
            [
                [
                    'style' => [
                        'background-image' => 'url(data:image/jpeg;base64,hash)',
                    ],
                ],
                [
                    'style' => 'background-image:url(data:image/jpeg;base64,hash);',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function idDataProvider(): array
    {
        return [
            ['foo', '-foo-'],
            ['foo-bar', 'foo--bar'],
            ['foo-bar-baz', 'foo[bar][baz]'],
            ['foo-bar-baz', 'foo bar baz'],
            ['foo.bar', 'foo.bar'],
            ['foo-bar', 'foo bar'],
            ['100', '100'],
            ['100-foo-bar', '100-foo-bar'],
            ['__FOO__ bar', '__FOO__ bar'],
        ];
    }

    /**
     * @return array
     */
    public static function namespaceInputNameDataProvider(): array
    {
        return [
            ['foo[bar]', 'bar', 'foo'],
            ['foo[bar][baz]', 'bar[baz]', 'foo'],
            ['foo', 'foo', null],
        ];
    }

    /**
     * @return array
     */
    public static function namespaceIdDataProvider(): array
    {
        return [
            ['foo-bar', 'bar', 'foo'],
            ['foo-bar-baz', 'bar[baz]', 'foo'],
            ['foo-bar-baz', 'baz', 'foo[bar]'],
            ['foo-bar', 'foo[bar]', null],
            ['__foo__', '__foo__', null],
            ['__FOO__', '__FOO__', null],
            ['__FOO_BAR__', '__FOO_BAR__', null],
            ['__FOO_BAR__-baz', '__FOO_BAR__-baz', null],
        ];
    }

    /**
     * @return array
     */
    public static function namespaceInputsDataProvider(): array
    {
        return [
            ['<input name="foo[bar]">', '<input name="bar">', 'foo'],
            ['<input name="foo[bar][baz]">', '<input name="bar[baz]">', 'foo'],
            ['<textarea name="foo[bar]"></textarea>', '<textarea name="bar"></textarea>', 'foo'],
            ['<textarea name="foo[bar]">blah</textarea>', '<textarea name="bar">blah</textarea>', 'foo'],
            ['<textarea name="foo[bar]"><input name="foo"></textarea>', '<textarea name="bar"><input name="foo"></textarea>', 'foo'],
            ['<input name="3[foo]">', '<input name="foo">', '3'],
        ];
    }

    /**
     * @return array
     */
    public static function namespaceAttributesDataProvider(): array
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
            // https://github.com/craftcms/cms/pull/13251
            ['<style>.foo-a, .foo-b:hover</style>', '<style>.a, .b:hover</style>', 'foo', true],
            ['<div id="foo-bar"></div><div data-reverse-target="#foo-bar, .foo"></div>', '<div id="bar"></div><div data-reverse-target="#bar, .foo"></div>', 'foo', false],
            ['<div id="foo-bar"></div><div data-reverse-target="#foo-bar, #foo-bar .foo"></div>', '<div id="bar"></div><div data-reverse-target="#bar, #bar .foo"></div>', 'foo', false],
            ['<div id="foo-bar"></div><div data-target-prefix="#foo-"></div>', '<div id="bar"></div><div data-target-prefix="#"></div>', 'foo', false],
            ['<div id="foo-bar"></div><div data-target-prefix></div>', '<div id="bar"></div><div data-target-prefix></div>', 'foo', false],
        ];
    }

    /**
     * @return array
     */
    public static function widontDataProvider(): array
    {
        return [
            ['foo', 'foo'],
            ['foo&nbsp;bar', 'foo bar'],
            ['foo bar&nbsp;baz', 'foo bar baz'],
        ];
    }

    /**
     * @return array
     */
    public static function encodeInvalidTagsDataProvider(): array
    {
        return [
            ['foo<br>bar', 'foo<br>bar'],
            ['foo<br/>bar', 'foo<br/>bar'],
            ['foo<br>bar&lt;p&gt;baz', 'foo<br>bar<p>baz'],
            ['foo&lt;p&gt;bar<br>baz', 'foo<p>bar<br>baz'],
            ['This text goes within the &lt;title&gt; tag in the &lt;head&gt; of the HTML file.', 'This text goes within the <title> tag in the <head> of the HTML file.'],
            ['Foo &lt;p&gt; bar <input type="hidden"', 'Foo <p> bar <input type="hidden"'],
        ];
    }
}
