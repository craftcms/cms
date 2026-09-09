<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementIndexState;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User\Elements\User;

beforeEach(function () {
    $this->indexState = app(ElementIndexState::class);
});

/**
 * Sets up entry sources as: `*`, a "Custom" heading, `custom:a`, `custom:b`
 * (with a nested `custom:b-child`), `custom:c`, and a trailing empty heading.
 */
function setUpRestrictableEntrySources(): void
{
    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class), [
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => '*',
        ],
        [
            'type' => ElementSources::TYPE_HEADING,
            'heading' => 'Custom',
        ],
        [
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:a',
            'label' => 'A',
        ],
        [
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:b',
            'label' => 'B',
            'nested' => [
                [
                    'type' => ElementSources::TYPE_CUSTOM,
                    'key' => 'custom:b-child',
                    'label' => 'B child',
                ],
            ],
        ],
        [
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:c',
            'label' => 'C',
        ],
        [
            'type' => ElementSources::TYPE_HEADING,
            'heading' => 'Trailing',
        ],
    ]);
}

/** @param iterable<int, array<string, mixed>> $sources */
function sourceKeys(iterable $sources): array
{
    $keys = [];

    foreach ($sources as $source) {
        $keys[] = $source['type'] === ElementSources::TYPE_HEADING
            ? sprintf('heading:%s', $source['heading'] ?? '')
            : $source['key'];
    }

    return $keys;
}

it('returns every source when not restricted', function () {
    setUpRestrictableEntrySources();

    expect(sourceKeys($this->indexState->sources(Entry::class, ElementSources::CONTEXT_MODAL)))
        ->toBe(['*', 'heading:Custom', 'custom:a', 'custom:b', 'custom:c', 'heading:Trailing']);
});

it('restricts sources to the given keys, in source order', function () {
    setUpRestrictableEntrySources();

    // Requested out of order — the source list's own order wins.
    $sources = $this->indexState->sources(
        Entry::class,
        ElementSources::CONTEXT_MODAL,
        restrictTo: ['custom:c', 'custom:a'],
    );

    expect(sourceKeys($sources))->toBe(['heading:Custom', 'custom:a', 'custom:c']);
});

it('drops headings left with no sources under them when restricting', function () {
    setUpRestrictableEntrySources();

    // `*` sits above the "Custom" heading, so that heading is left empty and
    // pruned, as is the always-trailing one.
    $sources = $this->indexState->sources(
        Entry::class,
        ElementSources::CONTEXT_MODAL,
        restrictTo: ['*'],
    );

    expect(sourceKeys($sources))->toBe(['*']);
});

it('pulls in restricted source keys that are nested, after the key they follow', function () {
    setUpRestrictableEntrySources();

    $sources = $this->indexState->sources(
        Entry::class,
        ElementSources::CONTEXT_MODAL,
        restrictTo: ['custom:a', 'custom:b/custom:b-child', 'custom:c'],
    );

    expect(sourceKeys($sources))->toBe(['heading:Custom', 'custom:a', 'custom:b-child', 'custom:c']);
});

it('appends a nested source key when the key it follows was not included', function () {
    setUpRestrictableEntrySources();

    $sources = $this->indexState->sources(
        Entry::class,
        ElementSources::CONTEXT_MODAL,
        restrictTo: ['custom:b/custom:b-child', 'custom:c'],
    );

    expect(sourceKeys($sources))->toBe(['heading:Custom', 'custom:c', 'custom:b-child']);
});

it('ignores restricted source keys that do not exist', function () {
    setUpRestrictableEntrySources();

    $sources = $this->indexState->sources(
        Entry::class,
        ElementSources::CONTEXT_MODAL,
        restrictTo: ['custom:a', 'nope'],
    );

    expect(sourceKeys($sources))->toBe(['heading:Custom', 'custom:a']);
});

it('returns an empty source list when nothing matches the restriction', function () {
    setUpRestrictableEntrySources();

    $sources = $this->indexState->sources(
        Entry::class,
        ElementSources::CONTEXT_MODAL,
        restrictTo: ['nope'],
    );

    expect($sources->all())->toBe([]);
});

it('shows the sidebar only when there is a real choice of sources', function () {
    $heading = ['type' => ElementSources::TYPE_HEADING, 'heading' => 'Heading'];
    $source = fn (string $key, array $extra = []) => ['type' => ElementSources::TYPE_NATIVE, 'key' => $key] + $extra;

    expect($this->indexState->showSidebar([]))->toBeFalse()
        ->and($this->indexState->showSidebar([$heading]))->toBeFalse()
        ->and($this->indexState->showSidebar([$heading, $source('a')]))->toBeFalse()
        ->and($this->indexState->showSidebar([$source('a'), $source('b')]))->toBeTrue()
        ->and($this->indexState->showSidebar([$source('a', ['nested' => [$source('b')]])]))->toBeTrue()
        ->and($this->indexState->showSidebar([$source('a', ['nested' => []])]))->toBeFalse();
});

it('normalizes a bare label sort option against its key', function () {
    expect($this->indexState->normalizeSortOption('Title', 'title'))->toBe([
        'key' => 'title',
        'label' => 'Title',
        'attribute' => 'title',
        'defaultDir' => 'asc',
        'option' => 'Title',
    ]);
});

it('normalizes an array sort option, falling back from attribute to orderBy', function () {
    $withAttribute = ['label' => 'Title', 'attribute' => 'title', 'orderBy' => 'content.title', 'defaultDir' => 'desc'];
    $withOrderBy = ['label' => 'Slug', 'orderBy' => 'slug'];

    expect($this->indexState->normalizeSortOption($withAttribute, 'title'))
        ->toBe([
            'key' => 'title',
            'label' => 'Title',
            'attribute' => 'title',
            'defaultDir' => 'desc',
            'option' => $withAttribute,
        ])
        ->and($this->indexState->normalizeSortOption($withOrderBy, 'slug'))
        ->toBe([
            'key' => 'slug',
            'label' => 'Slug',
            'attribute' => 'slug',
            'defaultDir' => 'asc',
            'option' => $withOrderBy,
        ]);
});

it('leaves a non-string orderBy in place so callers can filter it out', function () {
    $expression = new stdClass;
    $option = ['label' => 'Structure', 'orderBy' => $expression];

    $normalized = $this->indexState->normalizeSortOption($option, 'structure');

    expect($normalized['attribute'])->toBe($expression)
        ->and($normalized['label'])->toBe('Structure');
});

it('normalizes an element type sort options into a list', function () {
    $options = $this->indexState->sortOptions(Entry::class);

    expect($options->keys()->all())->toBe(range(0, $options->count() - 1))
        ->and($options->firstWhere('attribute', 'id'))->not->toBeNull()
        ->and($options->firstWhere('attribute', 'id')['label'])->toBe('ID');
});

it('merges a source’s table columns in only when a source key is given', function () {
    $common = $this->indexState->tableColumns(Entry::class);
    $forSource = $this->indexState->tableColumns(Entry::class, '*');

    expect($common->all())->toBe(app(ElementSources::class)->getAvailableTableAttributes(Entry::class)->all())
        ->and($forSource->keys()->all())->toContain(...$common->keys()->all());
});

it('resolves the site menu setting', function () {
    expect($this->indexState->showSiteMenu(Entry::class))->toBe(Entry::isLocalized())
        ->and($this->indexState->showSiteMenu(User::class))->toBe(User::isLocalized())
        ->and($this->indexState->showSiteMenu(Entry::class, false))->toBeFalse()
        ->and($this->indexState->showSiteMenu(User::class, '1'))->toBeTrue();
});

it('resolves the status menu setting', function () {
    expect($this->indexState->showStatusMenu(Entry::class))->toBeTrue()
        ->and($this->indexState->showStatusMenu(Entry::class, false))->toBeFalse()
        ->and($this->indexState->showStatusMenu(Entry::class, '0'))->toBeFalse();
});
