<?php

declare(strict_types=1);

use CraftCms\Cms\Import\Import;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->fixturePath = fn (string $name) => dirname(__DIR__, 2).'/Fixtures/Import/'.$name;
});

it('reads and formats a JSON file into rows', function () {
    $data = $this->import->getFormattedData(($this->fixturePath)('entries-plain-text.json'));

    expect($data)->toHaveCount(3)
        ->and($data[0]['title'])->toBe('first file entry')
        ->and($data[0]['plainText'])->toBe('text from the file')
        ->and($data[1]['slug'])->toBe('second-file-entry-slug');
});

it('reads and formats a CSV file into rows keyed by the heading row', function () {
    $data = $this->import->getFormattedData(($this->fixturePath)('entries.csv'));

    expect($data)->toHaveCount(2)
        ->and($data[0])->toBe([
            'sectionId' => 'fixtureSection',
            'typeId' => 'fixtureType',
            'title' => 'first csv entry',
            'plainText' => 'csv text one',
        ]);
});

it('reads and formats an XML file into rows, unwrapping the root element', function () {
    $data = $this->import->getFormattedData(($this->fixturePath)('entries.xml'));

    expect($data)->toHaveCount(2)
        ->and($data[0]['title'])->toBe('first xml entry')
        ->and($data[1]['plainText'])->toBe('xml text two');
});

it('throws when the file cannot be read', function () {
    expect(fn () => $this->import->getRawData(($this->fixturePath)('does-not-exist.json')))
        ->toThrow(Exception::class);
});

it('throws for an empty file', function () {
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('empty.json')))
        ->toThrow(Exception::class, 'Unable to parse data.');
});

it('throws the data type’s own error for malformed JSON', function () {
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('broken.json')))
        ->toThrow(Exception::class);
});

it('reports an unsupported file extension as an unsupported data type', function () {
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('unsupported.txt')))
        ->toThrow(Exception::class, 'Unsupported data type: txt');
});

it('reports an unsupported file extension when reading headings too', function () {
    expect(fn () => $this->import->getDataHeadings(($this->fixturePath)('unsupported.txt')))
        ->toThrow(Exception::class, 'Unsupported data type: txt');
});

it('returns the source headings with a "Please select" option prepended', function () {
    $headings = $this->import->getDataHeadings(($this->fixturePath)('entries-plain-text.json'));

    expect($headings[0])->toBe(['label' => 'Please select', 'value' => ''])
        ->and(array_column($headings, 'value'))->toContain('title', 'plainText', 'slug');
});
