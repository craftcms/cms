<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\ViewableConditionRule;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\LinkTypes\Entry;

test('new instances get a default “Viewable” selection condition', function () {
    $entry = new Entry;
    $condition = $entry->getSelectionCondition();

    expect($condition)->toBeInstanceOf(ElementCondition::class)
        ->and($condition->elementType)->toBe(EntryElement::class);

    $rules = $condition->getConditionRules()->getRules();

    expect($rules)->toHaveCount(1)
        ->and($rules[0])->toBeInstanceOf(ViewableConditionRule::class)
        ->and($rules[0]->value)->toBeTrue();
});

test('existing instances don’t get a selection condition seeded automatically', function () {
    $entry = new Entry(['showUnpermittedSections' => false]);

    expect($entry->getSelectionCondition())->toBeNull();
});

test('an explicit selection condition config is respected', function () {
    $entry = new Entry([
        'selectionCondition' => [
            'class' => EntryCondition::class,
            'elementType' => EntryElement::class,
            'conditionRules' => [
                ['class' => ViewableConditionRule::class, 'value' => false],
            ],
        ],
    ]);

    $rules = $entry->getSelectionCondition()?->getConditionRules()->getRules();

    expect($rules)->toHaveCount(1)
        ->and($rules[0])->toBeInstanceOf(ViewableConditionRule::class)
        ->and($rules[0]->value)->toBeFalse();
});

test('selection criteria no longer filters out unpermitted entries directly', function () {
    $entry = new Entry;
    $selectionCriteria = fn (): array => $this->selectionCriteria();

    expect($selectionCriteria->call($entry))->toBe(['uri' => 'not :empty:']);
});
