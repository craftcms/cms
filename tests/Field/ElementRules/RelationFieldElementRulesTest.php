<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Tests\TestClasses\FieldElementRulesHelper;

test('relation fields enforce min relations', function () {
    [$entry, $field] = FieldElementRulesHelper::createEntryWithField(
        handle: 'relatedEntries',
        fieldType: Entries::class,
        fieldSettings: ['allowLimit' => true, 'minRelations' => 1],
        value: [],
        scenario: Element::SCENARIO_LIVE,
    );

    $entry->validate();

    expect($entry->errors()->has('relatedEntries'))->toBeTrue();
});

test('relation fields validate related elements when enabled', function () {
    $relatedModel = EntryModel::factory()->create();
    $relatedEntry = EntryElement::find()->id($relatedModel->id)->one();
    $relatedEntry->title = '';

    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'relatedEntries',
        fieldType: Entries::class,
        fieldSettings: ['validateRelatedElements' => true],
        value: new ElementCollection([$relatedEntry]),
        scenario: Element::SCENARIO_LIVE,
    );

    $entry->validate();

    expect($entry->errors()->has('relatedEntries'))->toBeTrue();
});
