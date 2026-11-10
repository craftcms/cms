<?php

use craft\anchors\Plugin;
use CraftCms\Cms\Support\Facades\Anchors;

beforeEach(function() {
    app()->forgetScopedInstances();
});

it('keeps the Anchors plugin API working through core', function() {
    $parser = Plugin::getInstance()->parser;

    expect($parser->parseHtml('<h1>One</h1><h2>Two Words</h2>', 'h2', lowercase: true))
        ->toStartWith('<h1>One</h1><span id="two-words"></span><h2>Two Words <a class="anchor"')
        ->and(Plugin::getInstance()->getParser()->generateAnchorName('Größe', 'de'))->toBe('groesse');
});

it('applies the plugin parser’s settings to every anchors call, as the plugin’s shared parser did', function() {
    $parser = Plugin::getInstance()->parser;
    $parser->anchorLinkText = '¶';
    $parser->anchorLinkClass = null;

    expect($parser->anchorLinkText)->toBe('¶')
        ->and(Anchors::parseHtml('<h2>Two</h2>'))->toBe('<span id="two"></span><h2>Two <a title="Direct link to Two" aria-label="Direct link to Two" href="#two">¶</a></h2>');
});
