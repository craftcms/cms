<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Support\Facades\Fields;

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
