<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Html;

test('EncodeParams', function (string $expected, string $html, array $variables) {
    $this->assertSame($expected, Html::encodeParams($html, $variables));
})->with(function () {
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
            '<p>Im a paragraph. What am i, !@#$%^&amp;*(){}|::"&lt;&gt;&lt;?&gt;/*-~`</p>!@#$%^&*(){}|::"<><?>/*-~`',
            $htmlTagString.'!@#$%^&*(){}|::"<><?>/*-~`',
            ['whatIsThis' => '!@#$%^&*(){}|::"<><?>/*-~`'],
        ],
        ['😘!@#$%^&amp;*(){}|::"&lt;&gt;&lt;?&gt;/*-~`, {variable2}', $pureVariableString, ['variable1' => '😘!@#$%^&*(){}|::"<><?>/*-~`']],
    ];
});

test('EncodeSpaces', function (string $expected, string $str) {
    $this->assertSame($expected, Html::encodeSpaces($str));
})->with([
    ['foo%20bar', 'foo bar'],
    ['foo%20%20bar', 'foo  bar'],
]);

test('DisableInputs', function (?string $expected, callable|string|null $html) {
    $this->assertSame($expected, Html::disableInputs($html));
})->with([
    [
        null,
        null,
    ],
    [
        '',
        '',
    ],
    [
        '<input type="text" name="foo" disabled>',
        '<input type="text" name="foo">',
    ],
    [
        '<input type="text" name="foo" disabled>',
        '<input type="text" name="foo" disabled>',
    ],
    [
        '<input type="text" disabled>',
        '<input type="text">',
    ],
    [
        '<div class="field"><div class="input ltr disabled"><input type="text" name="foo" disabled></div></div>',
        '<div class="field"><div class="input ltr"><input type="text" name="foo"></div></div>',
    ],
    [
        '<fieldset class="field"><div class="input ltr disabled"><input type="text" name="foo" disabled></div></fieldset>',
        '<fieldset class="field"><div class="input ltr"><input type="text" name="foo"></div></fieldset>',
    ],
    [
        '<div class="field"><div class="input ltr disabled"><input type="text" name="foo" disabled></div></div>',
        '<div class="field"><div class="input ltr disabled"><input type="text" name="foo"></div></div>',
    ],
    [
        null,
        fn () => null,
    ],
    [
        '',
        fn () => '',
    ],
    [
        '<input type="text" name="foo" disabled>',
        fn () => '<input type="text" name="foo">',
    ],
    // https://github.com/nystudio107/craft-retour/issues/329
    [
        '<style>foo { color: red; }</style><input type="text" name="foo" disabled>',
        '<style>foo { color: red; }</style><input type="text" name="foo">',
    ],
]);

test('ParseTag', function (array|false $expected, string $tag): void {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        Html::parseTag($tag);
    } else {
        $this->assertSame($expected, normalizeParseTagInfo(Html::parseTag($tag)));
    }
})->with([
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
]);

function normalizeParseTagInfo(array $info): array
{
    if ($info['type'] === 'text') {
        return ['text', $info['value']];
    }

    return [
        $info['type'],
        $info['attributes'],
        array_map('normalizeParseTagInfo', $info['children']),
    ];
}

test('AppendToTag', function (string|false $expected, string $tag, string $html, ?string $ifExists): void {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        Html::appendToTag($tag, $html, $ifExists);
    } else {
        $this->assertSame($expected, Html::appendToTag($tag, $html, $ifExists));
    }
})->with([
    ['<div><p>Foo</p><p>Bar</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', null],
    ['<div><p>Foo</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'keep'],
    ['<div><p>Bar</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'replace'],
    [false, '<div />', '<p>Bar</p>', null],
    [false, '<div><p>Foo</p></div>', 'Bar', 'keep'],
]);

test('PrependToTag', function (string|false $expected, string $tag, string $html, ?string $ifExists): void {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        Html::prependToTag($tag, $html, $ifExists);
    } else {
        $this->assertSame($expected, Html::prependToTag($tag, $html, $ifExists));
    }
})->with([
    ['<div><p>Foo</p><p>Bar</p></div>', '<div><p>Bar</p></div>', '<p>Foo</p>', null],
    ['<div><p>Foo</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'keep'],
    ['<div><p>Bar</p></div>', '<div><p>Foo</p></div>', '<p>Bar</p>', 'replace'],
    [false, '<div />', '<p>Bar</p>', null],
    [false, '<div><p>Foo</p></div>', 'Bar', 'keep'],
]);

test('ParseTagAttributes', function (array|false $expected, string $tag): void {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        Html::parseTagAttributes($tag);
    } else {
        $this->assertSame($expected, Html::parseTagAttributes($tag));
    }
})->with([
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
    [['data-foo' => '1', 'data-bar' => '2'], '<div data-foo="1" data-bar="2">'],
    [['data-ng-foo' => '1', 'data-ng-bar' => '2'], '<div data-ng-foo="1" data-ng-bar="2">'],
    [['ng-foo' => '1', 'ng-bar' => '2'], '<div ng-foo="1" ng-bar="2">'],
    [['data-foo' => true], '<div data-foo>'],
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
    [['data-label' => "foo\n\nbar"], "<div data-label=\"foo\n\nbar\">"],
]);

test('ModifyTagAttributes', function (string|false $expected, string $tag, array $attributes): void {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);
        Html::modifyTagAttributes($tag, $attributes);
    } else {
        $this->assertSame($expected, Html::modifyTagAttributes($tag, $attributes));
    }
})->with([
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
]);

test('NormalizeTagAttributes', function (array $expected, array $attributes): void {
    expect(Html::normalizeTagAttributes($attributes))->toBe($expected);
})->with([
    [
        ['type' => 'text', 'disabled' => true],
        ['type' => 'text', 'disabled' => true],
    ],
    [
        ['class' => ['foo', 'bar']],
        ['class' => 'foo bar'],
    ],
    [
        ['style' => ['color' => 'black', 'background' => 'red']],
        ['style' => 'color: black; background: red;'],
    ],
    [
        ['data-foo' => '1', 'data-bar' => '2'],
        ['data' => ['foo' => '1', 'bar' => '2']],
    ],
    [
        ['data-ng-foo' => '1', 'data-ng-bar' => '2'],
        ['data-ng' => ['foo' => '1', 'bar' => '2']],
    ],
    [
        ['ng-foo' => '1', 'ng-bar' => '2'],
        ['ng' => ['foo' => '1', 'bar' => '2']],
    ],
    [
        ['data-foo' => true],
        ['data' => ['foo' => true]],
    ],
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
]);

test('Id', function (?string $expected, string $id): void {
    if ($expected) {
        $this->assertSame($expected, Html::id($id));
    } else {
        $this->assertEquals(10, strlen(Html::id($id)));
    }
})->with([
    ['foo', '-foo-'],
    ['foo-bar', 'foo--bar'],
    ['foo-bar-baz', 'foo[bar][baz]'],
    ['foo-bar-baz', 'foo bar baz'],
    ['foo.bar', 'foo.bar'],
    ['foo-bar', 'foo bar'],
    ['100', '100'],
    ['100-foo-bar', '100-foo-bar'],
    ['__FOO__ bar', '__FOO__ bar'],
]);

test('NamespaceInputName', function (string $expected, string $name, ?string $namespace): void {
    $this->assertSame($expected, Html::namespaceInputName($name, $namespace));
})->with([
    ['foo[bar]', 'bar', 'foo'],
    ['foo[bar][baz]', 'bar[baz]', 'foo'],
    ['foo', 'foo', null],
]);

test('NamespaceId', function (string $expected, string $name, ?string $namespace): void {
    $this->assertSame($expected, Html::namespaceId($name, $namespace));
})->with([
    ['foo-bar', 'bar', 'foo'],
    ['foo-bar-baz', 'bar[baz]', 'foo'],
    ['foo-bar-baz', 'baz', 'foo[bar]'],
    ['foo-bar', 'foo[bar]', null],
    ['__foo__', '__foo__', null],
    ['__FOO__', '__FOO__', null],
    ['__FOO_BAR__', '__FOO_BAR__', null],
    ['__FOO_BAR__-baz', '__FOO_BAR__-baz', null],
]);

test('NamespaceInputs', function (string $expected, string $html, string $namespace): void {
    $this->assertSame($expected, Html::namespaceInputs($html, $namespace));
})->with([
    ['<input name="foo[bar]">', '<input name="bar">', 'foo'],
    ['<input name="foo[bar][baz]">', '<input name="bar[baz]">', 'foo'],
    ['<textarea name="foo[bar]"></textarea>', '<textarea name="bar"></textarea>', 'foo'],
    ['<textarea name="foo[bar]">blah</textarea>', '<textarea name="bar">blah</textarea>', 'foo'],
    ['<textarea name="foo[bar]"><input name="foo"></textarea>', '<textarea name="bar"><input name="foo"></textarea>', 'foo'],
    ['<input name="3[foo]">', '<input name="foo">', '3'],
]);

test('NamespaceAttributes', function (string $expected, string $html, string $namespace, bool $classNames): void {
    $this->assertSame($expected, Html::namespaceAttributes($html, $namespace, $classNames));
})->with([
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
]);

test('Widont', function (string $expected, string $string): void {
    $this->assertSame($expected, Html::widont($string));
})->with([
    ['foo', 'foo'],
    ['foo&nbsp;bar', 'foo bar'],
    ['foo bar&nbsp;baz', 'foo bar baz'],
]);

test('EncodeInvalidTags', function (string $expected, string $html): void {
    $this->assertSame($expected, Html::encodeInvalidTags($html));
})->with([
    ['foo<br>bar', 'foo<br>bar'],
    ['foo<br/>bar', 'foo<br/>bar'],
    ['foo<br>bar&lt;p&gt;baz', 'foo<br>bar<p>baz'],
    ['foo&lt;p&gt;bar<br>baz', 'foo<p>bar<br>baz'],
    ['This text goes within the &lt;title&gt; tag in the &lt;head&gt; of the HTML file.', 'This text goes within the <title> tag in the <head> of the HTML file.'],
    ['Foo &lt;p&gt; bar <input type="hidden"', 'Foo <p> bar <input type="hidden"'],
]);

test('DecodeDoubles', function (string $expected, string $html): void {
    $this->assertSame($expected, Html::decodeDoubles($html));
})->with([
    ['&lt;p&gt;', '&lt;p&gt;'],
    ['&lt;p&gt;', '&amp;lt;p&amp;gt;'],
    ['&amp;lt;p&amp;gt;', '&amp;amp;lt;p&amp;amp;gt;'],
]);

test('UnwrapNoscript', function (): void {
    // Without <noscript>>
    $cssFile = Html::cssFile('foo.css');
    $this->assertSame([$cssFile->render(), false], Html::unwrapNoscript($cssFile));

    // With <noscript>
    $noscriptCssFile = Html::tag('noscript', Html::cssFile('foo.css'));
    $this->assertSame([$cssFile->render(), true], Html::unwrapNoscript($noscriptCssFile));

    // Content with newlines
    $content = "foo\nbar\nbaz";
    $noscriptContent = str_replace($cssFile, $content, Html::tag('noscript', Html::cssFile('foo.css')));
    $this->assertSame([$content, true], Html::unwrapNoscript($noscriptContent));
});

test('Svg', function (): void {
    $path = dirname(__DIR__, 1).'/_data/assets/files/craft-logo.svg';
    $contents = file_get_contents($path);

    $svg = Html::svg($path);
    $this->assertStringStartsWith('<svg', $svg);
    $this->assertStringContainsString('id="Symbols"', $svg);

    $svg = Html::svg($contents);
    $this->assertStringStartsWith('<svg', $svg);
    $this->assertMatchesRegularExpression('/id="\w+\-Symbols"/', $svg);

    $svg = Html::svg($contents, namespace: false);
    $this->assertStringStartsWith('<svg', $svg);
    $this->assertStringContainsString('id="Symbols"', $svg);
});
