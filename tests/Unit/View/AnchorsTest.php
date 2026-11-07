<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\AnchorsConfig;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\Anchors;

beforeEach(function () {
    $this->anchors = app(Anchors::class);
});

it('adds an anchor and a trailing anchor link to h1–h3 headings by default', function () {
    $html = '<h2 class="intro">Hello World</h2><p>Body</p><h4>Skipped</h4>';

    expect($this->anchors->parseHtml($html))->toBe(
        '<span id="hello-world"></span><h2 class="intro">Hello World <a class="anchor" title="Direct link to Hello World" aria-label="Direct link to Hello World" href="#hello-world">#</a></h2><p>Body</p><h4>Skipped</h4>',
    );
});

it('only adds anchors to the given tags', function () {
    $html = '<h1>One</h1><h4>Four</h4>';

    expect($this->anchors->parseHtml($html, 'h4'))
        ->toStartWith('<h1>One</h1><span id="four"></span><h4>')
        ->and($this->anchors->parseHtml($html, ['h1']))
        ->toStartWith('<span id="one"></span><h1>')
        ->toEndWith('<h4>Four</h4>');
});

it('puts the id on the heading itself, keeping an existing one, when the additional tag is disabled', function () {
    Cms::config()->anchors(new AnchorsConfig()
        ->useAdditionalTagToAnchorTo(false)
        ->anchorLinkPosition(AnchorsConfig::POS_BEFORE)
        ->anchorLinkClass(null)
        ->anchorLinkText('¶'));

    expect($this->anchors->parseHtml('<h2>New Heading</h2><h3 id="custom">Kept</h3>'))->toBe(
        '<h2 id="new-heading"><a title="Direct link to New Heading" aria-label="Direct link to New Heading" href="#new-heading">¶</a> New Heading</h2>'.
        '<h3 id="custom"><a title="Direct link to Kept" aria-label="Direct link to Kept" href="#custom">¶</a> Kept</h3>',
    );
});

it('reads the heading’s actual id attribute', function (string $heading, string $expectedOpeningTag, string $expectedHref) {
    Cms::config()->anchors(new AnchorsConfig()->useAdditionalTagToAnchorTo(false));

    expect($this->anchors->parseHtml($heading))
        ->toStartWith($expectedOpeningTag)
        ->toContain("href=\"$expectedHref\"");
})->with([
    'hyphenated id' => ['<h2 id="intro-section">Intro</h2>', '<h2 id="intro-section">Intro ', '#intro-section'],
    'single-quoted id' => ["<h2 id='single'>Single</h2>", "<h2 id='single'>Single ", '#single'],
    'encoded id' => ['<h2 id="a&amp;b">Amp</h2>', '<h2 id="a&amp;b">Amp ', '#a&amp;b'],
    'data-id is not the id' => ['<h2 data-id="metadata">Data</h2>', '<h2 data-id="metadata" id="data">', '#data'],
]);

it('generates anchor names from heading text', function (string $heading, ?string $language, bool $lowercase, string $expected) {
    expect($this->anchors->generateAnchorName($heading, $language, $lowercase))->toBe($expected);
})->with([
    'punctuation, markup, and parentheses' => ['It’s <em>Not</em> (really) “Done” &amp; NASA-ready', null, false, 'its-not-done-nasa-ready'],
    'lowercase' => ['Hello NASA World', null, true, 'hello-nasa-world'],
    'language-specific ASCII' => ['Größe', 'de', false, 'groesse'],
    'default ASCII' => ['Größe', null, false, 'grosse'],
]);

it('provides the |anchors Twig filter', function () {
    $html = app(Twig::class)->create()
        ->createTemplate('{{ html|anchors("h2", lowercase=true) }}')
        ->render(['html' => '<h1>Top</h1><h2>Hello NASA</h2>']);

    expect($html)->toStartWith('<h1>Top</h1><span id="hello-nasa"></span><h2>Hello NASA <a class="anchor"');
});
