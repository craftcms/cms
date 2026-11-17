<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Structures;

test('relation fields with maintainHierarchy can serialize an ElementCollection value directly', function () {
    $relatedModel = EntryModel::factory()->create();
    $relatedEntry = EntryElement::find()->id($relatedModel->id)->one();

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, ['maintainHierarchy' => true])
        ->createElementWithFields(save: false);

    $field = Fields::getFieldByHandle('relatedEntries');

    $serialized = $field->serializeValue(new ElementCollection([$relatedEntry]), $result->element);

    expect($serialized)->toBe([$relatedEntry->id]);
});

test('eager-loading relation fields with maintainHierarchy does not log deprecations', function () {
    $relatedModel = EntryModel::factory()->create();

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, ['maintainHierarchy' => true], value: [$relatedModel->id])
        ->createElementWithFields();

    $entry = EntryElement::find()->id($result->element->id)->with(['relatedEntries'])->one();

    expect($entry->relatedEntries->ids()->all())->toBe([$relatedModel->id])
        ->and(collect(array_keys(Deprecator::getRequestLogs()))
            ->filter(fn (string $key) => str_contains($key, '::orderBy'))
            ->all())->toBe([]);
});

test('relation fields with maintainHierarchy relate and show the missing ancestors of selected elements', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);
    Sections::refreshSections();
    $news = EntryModel::factory()->forSection($section)->createElement(['title' => 'News']);
    $clubNews = EntryModel::factory()->forSection($section)->createElement(['title' => 'Club news']);
    $events = EntryModel::factory()->forSection($section)->createElement(['title' => 'Events']);
    Structures::appendToRoot($structure->id, $news);
    Structures::append($structure->id, $clubNews, $news);
    Structures::appendToRoot($structure->id, $events);

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, [
            'maintainHierarchy' => true,
            'sources' => ["section:{$section->uid}"],
            'viewMode' => 'cards',
        ], value: [$events->id, $clubNews->id])
        ->createElementWithFields();
    $owner = EntryElement::find()->id($result->element->id)->one();
    $field = Fields::getFieldByHandle('relatedEntries');

    $control = $field->uiControl(new FieldContext(
        path: 'relatedEntries',
        element: $owner,
        value: $owner->getFieldValue('relatedEntries'),
    ));
    $props = $control->props($control->getValue());

    expect($field->serializeValue($owner->getFieldValue('relatedEntries'), $owner))->toBe([$news->id, $clubNews->id, $events->id])
        ->and(array_map(fn (array $element) => [$element['label'], $element['level']], $props['elements']))->toBe([
            ['News', 1],
            ['Club news', 2],
            ['Events', 1],
        ])
        ->and($props['maintainHierarchy'])->toBeTrue()
        ->and($props['viewMode'])->toBe('list');
});
