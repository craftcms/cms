<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Events\ElementSourceSortOptionsResolving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
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

it('puts sources that aren’t saved on the first page with an enabled source', function () {
    $channel = Section::factory()->create(['type' => SectionType::Channel]);
    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class), [
        ['type' => ElementSources::TYPE_HEADING, 'heading' => 'Archive', 'page' => 'Archive'],
        ['type' => ElementSources::TYPE_CUSTOM, 'key' => 'custom:archive', 'label' => 'Archive', 'page' => 'Archive', 'disabled' => true],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
    ]);

    expect($this->elementSources->findSource(Entry::class, "section:$channel->uid")['page'] ?? null)->toBe('Entries');
});

it('limits sources to their page when the first source is disabled', function () {
    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class), [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'News', 'disabled' => true],
        ['type' => ElementSources::TYPE_CUSTOM, 'key' => 'custom:news', 'label' => 'News', 'page' => 'News'],
        ['type' => ElementSources::TYPE_CUSTOM, 'key' => 'custom:events', 'label' => 'Events', 'page' => 'Events'],
    ]);

    expect($this->elementSources->getSources(Entry::class, page: 'Events')->pluck('key')->all())->toBe(['custom:events'])
        ->and($this->elementSources->getSources(Entry::class, page: 'News')->pluck('key')->all())->toBe(['custom:news']);
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

it('generates a page slug with the same name id as the page name', function (string $name, string $slug) {
    expect($this->elementSources->pageSlug($name))->toBe($slug)
        ->and($this->elementSources->pageNameId($slug))->toBe($this->elementSources->pageNameId($name));
})->with([
    'plain' => ['Blog', 'blog'],
    'spaces and punctuation' => ['News & Events', 'news-events'],
    'apostrophe' => ['Don’t Miss', 'dont-miss'],
    'non-Latin' => ['Αναρτήσεις', 'αναρτήσεις'],
    'accented' => ['Événements', 'événements'],
    'combining mark' => ["Cafe\u{0301}s", "cafe\u{0301}s"],
    'at sign' => ['Q @ A', 'q-a'],
]);

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

it('rejects invalid custom source conditions before writing project config', function () {
    $condition = new ElementCondition(Entry::class);
    $condition->addConditionRule($condition->createConditionRule([
        'class' => TitleConditionRule::class,
        'operator' => 'invalid',
    ]));
    $path = sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class);
    $original = app(ProjectConfig::class)->get($path);

    expect(fn () => $this->elementSources->saveSources(Entry::class, [
        ['type' => ElementSources::TYPE_CUSTOM, 'key' => 'custom:test', 'condition' => $condition],
    ]))->toThrow(ValidationException::class);
    expect(app(ProjectConfig::class)->get($path))->toBe($original);
});

it('offers named generated fields as table columns', function () {
    $layout = FieldLayout::createFromConfig(['type' => Entry::class])->generatedFields([
        ['uid' => 'generated-named', 'name' => 'Status label', 'handle' => 'statusLabel', 'template' => '{title|upper}'],
        ['uid' => 'generated-unnamed', 'name' => '', 'handle' => 'unnamed', 'template' => '{title}'],
    ]);

    $attributes = $this->elementSources->getTableAttributes(
        Entry::class,
        'section:any',
        ['generatedField:generated-named', 'generatedField:generated-unnamed'],
        [$layout],
    );

    expect($attributes->all())->toBe([
        ['title', ['label' => 'Entry']],
        ['generatedField:generated-named', ['label' => 'Status label']],
    ]);
});

it('aligns field columns to the end for quantities', function () {
    $fields = collect([
        'plainText' => [PlainText::class, []],
        'quantity' => [Number::class, []],
        'code' => [Number::class, ['previewFormat' => Number::FORMAT_NONE]],
        'price' => [Money::class, []],
        'rating' => [Range::class, []],
    ])->map(function (array $config, string $handle) {
        [$type, $settings] = $config;
        $field = Fields::createField([
            'type' => $type,
            'name' => $handle,
            'handle' => $handle,
            'settings' => $settings,
        ]);
        expect(Fields::saveField($field))->toBeTrue();

        return $field;
    });

    $layout = FieldLayout::createFromConfig([
        'type' => Entry::class,
        'tabs' => [[
            'name' => 'Content',
            'elements' => $fields
                ->map(fn ($field) => ['type' => CustomField::class, 'fieldUid' => $field->uid])
                ->values()
                ->all(),
        ]],
    ]);

    $attributes = $this->elementSources->getTableAttributesForFieldLayouts([$layout]);

    expect($fields->map(fn ($field) => $attributes["field:$field->uid"]['align'])->all())->toBe([
        'plainText' => 'start',
        'quantity' => 'end',
        'code' => 'start',
        'price' => 'end',
        'rating' => 'end',
    ]);
});
