<?php

declare(strict_types=1);

use CraftCms\Cms\Import\DataTypes\Xml;

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

// A single <entry> unwraps to the row itself, not a list of one.
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

it('reports empty data instead of throwing', function () {
    expect(Xml::format(''))->toBe(['success' => false, 'error' => 'Invalid XML: The data must be an XML document.'])
        ->and(Xml::getHeadings(''))->toBe(['success' => false, 'error' => 'Invalid XML: The data must be an XML document.']);
});

it('formats an empty root element into no rows', function () {
    expect(Xml::format('<entries/>'))->toBe(['success' => true, 'data' => []])
        ->and(Xml::getHeadings('<entries/>'))->toBe([]);
});

it('returns dot-notation headings for XML elements', function () {
    $result = Xml::getHeadings(XML);

    expect(array_column($result, 'value'))->toContain('title', 'plainText');
});
