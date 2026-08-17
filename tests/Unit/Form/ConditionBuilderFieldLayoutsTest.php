<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Form\Controls\ConditionBuilder;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\Conditions;

it('advertises field layouts on the resolved payload', function () {
    $layout = ['type' => Entry::class, 'tabs' => []];
    $form = Form::make([
        Field::make('Condition', ConditionBuilder::make('elementCondition')
            ->conditionClass(ElementCondition::class)
            ->fieldLayouts([$layout])),
    ]);

    $payload = app(FormResolver::class)->resolve($form, new FormContext);

    expect($payload->nodes[0]->control->props['fieldLayouts'])->toBe([$layout]);
});

it('defaults field layouts to an empty list', function () {
    $form = Form::make([
        Field::make('Condition', ConditionBuilder::make('elementCondition')
            ->conditionClass(ElementCondition::class)),
    ]);

    $payload = app(FormResolver::class)->resolve($form, new FormContext);

    expect($payload->nodes[0]->control->props['fieldLayouts'])->toBe([]);
});

it('hydrates field layout configs onto the condition', function () {
    $condition = Conditions::createCondition([
        'class' => ElementCondition::class,
        'elementType' => Entry::class,
        'fieldLayouts' => [['type' => Entry::class, 'tabs' => []]],
    ]);

    expect($condition)->toBeInstanceOf(ElementCondition::class)
        ->and($condition->getFieldLayouts())->toHaveCount(1)
        ->and($condition->getFieldLayouts()[0])->toBeInstanceOf(FieldLayout::class);
});

it('renders a builder for a condition seeded with field layouts', function () {
    $html = ConditionBuilder::builderHtml(
        [],
        ElementCondition::class,
        [],
        true,
        'elementCondition',
        false,
        [['type' => Entry::class, 'tabs' => []]],
    );

    expect($html)->toContain('elementCondition');
});
