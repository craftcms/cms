<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Search;

test('normalizeKeywords', function (string $expected, array|string $str, array $ignore = [], bool $processCharMap = true, ?string $language = null) {
    expect(Search::normalizeKeywords($str, $ignore, $processCharMap, $language))->toBe($expected);
})->with([
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
    ['toolbox', 'tool­box'],
    ['toolbox', 'tool‍box'],
    ['tool box', 'tool&nbsp;box'],
]);
