<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;

test('relation fields enforce min relations', function () {
    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, ['allowLimit' => true, 'minRelations' => 1], value: [])
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields();

    $result->element->validate();

    expect($result->element->errors()->has('relatedEntries'))->toBeTrue();
});

test('relation fields validate related elements when enabled', function () {
    $relatedModel = EntryModel::factory()->create();
    $relatedEntry = EntryElement::find()->id($relatedModel->id)->one();
    $relatedEntry->title = '';

    $result = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, ['validateRelatedElements' => true], value: new ElementCollection([$relatedEntry]))
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields();

    $result->element->validate();

    expect($result->element->errors()->has('relatedEntries'))->toBeTrue();
});
