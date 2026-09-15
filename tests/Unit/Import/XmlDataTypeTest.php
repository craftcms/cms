<?php

declare(strict_types=1);

use CraftCms\Cms\Import\DataTypes\Xml;

/** getData() unwraps the root element, so <entries><entry>… becomes a list of entry rows. */
const XML = <<<'XMLDATA'
<?xml version="1.0" encoding="UTF-8"?>
<entries>
    <entry>
        <title>first entry</title>
        <plainText>text one</plainText>
    </entry>
    <entry>
        <title>second entry</title>
        <plainText>text two</plainText>
    </entry>
</entries>
XMLDATA;

// format()

it('formats XML into rows, unwrapping the root element', function () {
    $result = Xml::format(XML);

    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toBe([
            ['title' => 'first entry', 'plainText' => 'text one'],
            ['title' => 'second entry', 'plainText' => 'text two'],
        ]);
});

it('keeps nested elements as nested arrays', function () {
    $xml = '<entries>'
        .'<entry><title>one</title><fields><text>x</text></fields></entry>'
        .'<entry><title>two</title><fields><text>y</text></fields></entry>'
        .'</entries>';

    $result = Xml::format($xml);

    expect($result['data'][0]['fields'])->toBe(['text' => 'x']);
});

// getData() unwraps the root's first key, which is the row list only when there are 2+ rows: with a
// single <entry> the unwrapped value is that row itself, so the result is one row rather than a
// list of one. Import::Import() foreaches whatever comes back, so this shape matters.
it('formats a single-row XML document into one row rather than a list of one', function () {
    $result = Xml::format('<entries><entry><title>only one</title></entry></entries>');

    expect($result['data'])->toBe(['title' => 'only one'])
        ->and($result['data'])->not()->toHaveKey(0);
});

it('reports malformed XML instead of throwing', function () {
    $result = Xml::format('<entries><entry><title>unterminated</entries>');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toStartWith('Invalid XML:');
});

// getHeadings()

it('returns dot-notation headings for XML elements', function () {
    $result = Xml::getHeadings(XML);

    expect(array_column($result, 'value'))->toContain('title', 'plainText');
});
