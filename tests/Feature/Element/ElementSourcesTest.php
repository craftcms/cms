<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\ProjectConfig\ProjectConfig;

beforeEach(function () {
    $this->elementSources = app(ElementSources::class);
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
    expect($this->elementSources->getSources(Entry::class)->count())->toBe(1);
});

it('can check if a source exists', function () {
    expect($this->elementSources->sourceExists(Entry::class, '*'))->toBeTrue();
    expect($this->elementSources->sourceExists(Entry::class, 'foo'))->toBeFalse();
});

it('can find nested source configs by key path', function () {
    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class), [
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => '*',
        ],
        [
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:parent',
            'label' => 'Parent',
            'nested' => [
                [
                    'key' => 'custom:child',
                    'label' => 'Child',
                ],
            ],
        ],
    ]);

    expect($this->elementSources->findSource(Entry::class, 'custom:parent/custom:child'))->toBe([
        'key' => 'custom:child',
        'label' => 'Child',
        'type' => ElementSources::TYPE_CUSTOM,
        'keyPath' => 'custom:parent/custom:child',
    ]);
});

it('can generate a page name id', function () {
    expect($this->elementSources->pageNameId('foo'))->toBe('foo');
    expect($this->elementSources->pageNameId('Another page'))->toBe('anotherpage');
});
