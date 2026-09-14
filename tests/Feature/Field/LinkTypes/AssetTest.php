<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Conditions\ViewableConditionRule;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Field\LinkTypes\Asset;

test('new instances get a default “Viewable” selection condition', function () {
    $asset = new Asset;
    $condition = $asset->getSelectionCondition();

    expect($condition)->toBeInstanceOf(ElementCondition::class)
        ->and($condition->elementType)->toBe(AssetElement::class);

    $rules = $condition->getConditionRules()->getRules();

    expect($rules)->toHaveCount(1)
        ->and($rules[0])->toBeInstanceOf(ViewableConditionRule::class)
        ->and($rules[0]->value)->toBeTrue();
});

test('existing instances don’t get a selection condition seeded automatically', function () {
    $asset = new Asset(['showUnpermittedVolumes' => false]);

    expect($asset->getSelectionCondition())->toBeNull();
});

test('an explicit selection condition config is respected', function () {
    $asset = new Asset([
        'selectionCondition' => [
            'class' => AssetCondition::class,
            'elementType' => AssetElement::class,
            'conditionRules' => [
                ['class' => ViewableConditionRule::class, 'value' => false],
                ['class' => FileTypeConditionRule::class, 'values' => ['image']],
            ],
        ],
    ]);

    $rules = $asset->getSelectionCondition()?->getConditionRules()->getRules();

    expect($rules)->toHaveCount(2)
        ->and($rules[0])->toBeInstanceOf(ViewableConditionRule::class)
        ->and($rules[0]->value)->toBeFalse()
        ->and($rules[1])->toBeInstanceOf(FileTypeConditionRule::class)
        ->and($rules[1]->getValues())->toBe(['image']);
});

test('selection criteria no longer filters by kind or uploader directly', function () {
    $asset = new Asset;
    $selectionCriteria = fn (): array => $this->selectionCriteria();

    expect($selectionCriteria->call($asset))->toBe(['uploaderId' => null]);
});
