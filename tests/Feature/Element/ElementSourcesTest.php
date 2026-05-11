<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Events\ElementSourceSortOptionsResolving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\Event;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

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

it('can get source sort options', function () {
    TestElementSourceSortOptionsElement::$requestedSources = [];
    $expressionA = new stdClass;
    $expressionB = new stdClass;

    Event::listen(ElementSourceSortOptionsResolving::class, function (ElementSourceSortOptionsResolving $event) use ($expressionA, $expressionB) {
        $event->sortOptions = collect(match ($event->source) {
            '__IMP__' => [
                [
                    'label' => 'Important',
                    'attribute' => 'important',
                    'orderBy' => 'important',
                    'defaultDir' => 'desc',
                ],
            ],
            'regular' => [
                [
                    'label' => 'Title',
                    'attribute' => 'title',
                    'orderBy' => 'title',
                    'defaultDir' => 'asc',
                ],
                [
                    'label' => 'First duplicate',
                    'attribute' => 'duplicate',
                    'orderBy' => 'firstColumn',
                    'defaultDir' => 'asc',
                ],
                [
                    'label' => 'Second duplicate',
                    'attribute' => 'duplicate',
                    'orderBy' => 'secondColumn',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => 'First expression duplicate',
                    'attribute' => 'expressionDuplicate',
                    'orderBy' => $expressionA,
                    'defaultDir' => 'asc',
                ],
                [
                    'label' => 'Second expression duplicate',
                    'attribute' => 'expressionDuplicate',
                    'orderBy' => $expressionB,
                    'defaultDir' => 'desc',
                ],
            ],
        });
    });

    expect($this->elementSources->getSourceSortOptions(TestElementSourceSortOptionsElement::class, '__IMP__')->all())
        ->toBe([
            'important' => [
                'label' => 'Important',
                'attribute' => 'important',
                'orderBy' => 'important',
                'defaultDir' => 'desc',
            ],
        ])
        ->and(TestElementSourceSortOptionsElement::$requestedSources)->toBe([null]);

    $sortOptions = $this->elementSources
        ->getSourceSortOptions(TestElementSourceSortOptionsElement::class, 'regular');

    expect(TestElementSourceSortOptionsElement::$requestedSources)->toBe([null, 'regular'])
        ->and($sortOptions->get('title'))->toBe([
            'label' => 'Title',
            'attribute' => 'title',
            'orderBy' => 'title',
            'defaultDir' => 'asc',
        ])
        ->and($sortOptions->get('duplicate')['orderBy'])->toBeInstanceOf(Coalesce::class)
        ->and($sortOptions->get('expressionDuplicate'))->toBe([
            'label' => 'First expression duplicate',
            'attribute' => 'expressionDuplicate',
            'orderBy' => $expressionA,
            'defaultDir' => 'asc',
        ]);
});

it('can generate a page name id', function () {
    expect($this->elementSources->pageNameId('foo'))->toBe('foo');
    expect($this->elementSources->pageNameId('Another page'))->toBe('anotherpage');
});

class TestElementSourceSortOptionsElement extends Element
{
    public static array $requestedSources = [];

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return 'Test Elements';
    }

    #[Override]
    protected static function defineFieldLayouts(?string $source): array
    {
        self::$requestedSources[] = $source;

        return [new FieldLayout([
            'type' => static::class,
        ])];
    }

    #[Override]
    public function getCanonical(bool $anySite = false): ElementInterface
    {
        return $this;
    }
}
