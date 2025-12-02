<?php

declare(strict_types=1);

use craft\elements\Entry;
use CraftCms\Cms\Element\ElementSources;

beforeEach(function () {
    $this->elementSources = resolve(ElementSources::class);
});

it('can filter out extra headings from a collection of sources', function () {
    expect(ElementSources::filterExtraHeadings([
        ['type' => ElementSources::TYPE_NATIVE, 'title' => 'Source 1'],
        ['type' => ElementSources::TYPE_HEADING, 'title' => 'Heading 1'],
        ['type' => ElementSources::TYPE_NATIVE, 'title' => 'Source 2'],
        ['type' => ElementSources::TYPE_HEADING, 'title' => 'Heading 2'],
        ['type' => ElementSources::TYPE_HEADING, 'title' => 'Heading 3'],
    ])->all())->toBe([
        ['type' => ElementSources::TYPE_NATIVE, 'title' => 'Source 1'],
        ['type' => ElementSources::TYPE_HEADING, 'title' => 'Heading 1'],
        ['type' => ElementSources::TYPE_NATIVE, 'title' => 'Source 2'],
    ]);
});

it('can get sources', function () {
    expect($this->elementSources->getSources(Entry::class))->toHaveCount(1);
});

it('can check if a source exists', function () {
    expect($this->elementSources->sourceExists(Entry::class, '*'))->toBeTrue();
    expect($this->elementSources->sourceExists(Entry::class, 'foo'))->toBeFalse();
});

it('can generate a page name id', function () {
    expect($this->elementSources->pageNameId('foo'))->toBe('foo');
    expect($this->elementSources->pageNameId('Another page'))->toBe('anotherpage');
});
