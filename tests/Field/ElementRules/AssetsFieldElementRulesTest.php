<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Tests\TestClasses\FieldElementRulesHelper;

test('assets field validates allowed file kinds', function () {
    $image = AssetModel::factory()->createElement([
        'filename' => 'photo.jpg',
        'kind' => 'image',
    ]);
    $pdf = AssetModel::factory()->createElement([
        'filename' => 'manual.pdf',
        'kind' => 'pdf',
    ]);

    [$validEntry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'allowedAssets',
        fieldType: Assets::class,
        fieldSettings: ['restrictFiles' => true, 'allowedKinds' => ['image']],
        value: AssetElement::find()->id($image->id),
        scenario: Element::SCENARIO_LIVE,
    );
    $validEntry->validate();

    expect($validEntry->errors()->has('allowedAssets'))->toBeFalse();

    [$invalidEntry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'blockedAssets',
        fieldType: Assets::class,
        fieldSettings: ['restrictFiles' => true, 'allowedKinds' => ['image']],
        value: AssetElement::find()->id($pdf->id),
        scenario: Element::SCENARIO_LIVE,
    );
    $invalidEntry->validate();

    expect($invalidEntry->errors()->has('blockedAssets'))->toBeTrue();
});
