<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Assets;

function allowedKindsSelectionCondition(array $kinds): array
{
    return [
        'class' => AssetCondition::class,
        'elementType' => AssetElement::class,
        'conditionRules' => [
            ['class' => FileTypeConditionRule::class, 'values' => $kinds],
        ],
    ];
}

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
        ->withField('allowedAssets', Assets::class, ['selectionCondition' => allowedKindsSelectionCondition(['image'])], value: AssetElement::find()->id($image->id))
        ->withScenario(ElementRules::SCENARIO_LIVE)
        ->createElementWithFields(save: false);
    $validResult->element->validate();

    expect($validResult->element->errors()->has('allowedAssets'))->toBeFalse();

    $invalidResult = EntryModel::factory()
        ->withField('blockedAssets', Assets::class, ['selectionCondition' => allowedKindsSelectionCondition(['image'])], value: AssetElement::find()->id($pdf->id))
        ->withScenario(ElementRules::SCENARIO_LIVE)
        ->createElementWithFields(save: false);
    $invalidResult->element->validate();

    expect($invalidResult->element->errors()->has('blockedAssets'))->toBeTrue();
});
