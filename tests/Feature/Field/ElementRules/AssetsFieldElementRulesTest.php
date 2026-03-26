<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Assets;

test('assets field validates allowed file kinds', function () {
    $image = AssetModel::factory()->createElement([
        'filename' => 'photo.jpg',
        'kind' => 'image',
    ]);
    $pdf = AssetModel::factory()->createElement([
        'filename' => 'manual.pdf',
        'kind' => 'pdf',
    ]);

    $validResult = EntryModel::factory()
        ->withField('allowedAssets', Assets::class, ['restrictFiles' => true, 'allowedKinds' => ['image']], value: AssetElement::find()->id($image->id))
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);
    $validResult->element->validate();

    expect($validResult->element->errors()->has('allowedAssets'))->toBeFalse();

    $invalidResult = EntryModel::factory()
        ->withField('blockedAssets', Assets::class, ['restrictFiles' => true, 'allowedKinds' => ['image']], value: AssetElement::find()->id($pdf->id))
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);
    $invalidResult->element->validate();

    expect($invalidResult->element->errors()->has('blockedAssets'))->toBeTrue();
});
