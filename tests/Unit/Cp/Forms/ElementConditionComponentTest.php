<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\ElementCondition;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;
use CraftCms\Cms\Http\Controllers\ConditionsController;

class TestElementCondition extends BaseCondition
{
    protected function selectableConditionRules(): array
    {
        return [];
    }

    protected function config(): array
    {
        return ['scope' => 'entries'];
    }
}

it('renders an executable condition through the shared primitive without mutating it', function () {
    $condition = new TestElementCondition([
        'id' => 'selection-condition',
        'sortable' => false,
        'addRuleLabel' => 'Add condition',
    ]);

    $html = ElementCondition::make()
        ->name('selectionCondition')
        ->condition($condition)
        ->readOnly()
        ->toHtml();

    expect($html)
        ->toContainTag('fieldset', [
            'id' => 'selection-condition',
            'class' => 'condition-container',
            'disabled' => true,
        ])
        ->toContainTag('input', [
            'name' => 'selectionCondition[class]',
            'value' => TestElementCondition::class,
        ])
        ->and($condition->name)->toBe('condition')
        ->and($condition->mainTag)->toBe('form')
        ->and($condition->forProjectConfig)->toBeFalse();
});

it('registers and deterministically projects serializable condition editor configuration', function () {
    $component = ElementCondition::make()
        ->name('selectionCondition')
        ->conditionClass(TestElementCondition::class)
        ->builderConfig(['scope' => 'entries'])
        ->sortable(false)
        ->addRuleLabel('Add condition')
        ->attributes(['data' => ['setting' => 'selection-condition']]);
    $form = Form::make([
        Field::make($component),
    ]);
    $expected = [
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:element-condition-input',
                'name' => 'selectionCondition',
                'props' => [
                    'conditionClass' => TestElementCondition::class,
                    'builderConfig' => ['scope' => 'entries'],
                    'renderUrl' => action([ConditionsController::class, 'show']),
                    'sortable' => false,
                    'addRuleLabel' => 'Add condition',
                ],
                'attributes' => ['data' => ['setting' => 'selection-condition']],
            ]],
        ]],
    ];
    $firstProjection = $form->toArray();

    expect(app(ComponentRegistry::class)->make('element-condition'))->toBeInstanceOf(ElementCondition::class)
        ->and(app(FormElementTypes::class)->isRegistered(ElementCondition::formElementType()))->toBeTrue()
        ->and($firstProjection)->toBe($expected)
        ->and($form->toArray())->toBe($firstProjection);
});

it('ignores host-owned condition editor state during projection', function (ElementCondition $component) {
    expect(Form::make([
        Field::make($component),
    ])->toArray())->toHaveKey('elements.0.children.0.name', 'selectionCondition');
})->with([
    'executable condition' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(TestElementCondition::class)
            ->condition(new TestElementCondition),
    ],
    'read-only authorization state' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(TestElementCondition::class)
            ->readOnly(false),
    ],
]);

it('rejects invalid portable condition editor configuration', function (ElementCondition $component, string $option) {
    expect(fn () => Form::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form output.', ElementCondition::class, $option),
    );
})->with([
    'name' => [fn () => ElementCondition::make()->conditionClass(TestElementCondition::class), 'name'],
    'condition class' => [fn () => ElementCondition::make()->name('selectionCondition')->conditionClass(stdClass::class), 'conditionClass'],
    'builder config' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(TestElementCondition::class)
            ->builderConfig(['condition' => new stdClass]),
        'builderConfig.condition',
    ],
    'sortable state' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(TestElementCondition::class)
            ->sortable(fn (): string => 'yes'),
        'sortable',
    ],
    'add rule label' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(TestElementCondition::class)
            ->addRuleLabel(fn (): int => 1),
        'addRuleLabel',
    ],
]);

it('fails HTML rendering when an executable condition is missing or incompatible', function (ElementCondition $component, string $option) {
    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for HTML output.', ElementCondition::class, $option),
    );
})->with([
    'missing condition' => [fn () => ElementCondition::make()->name('selectionCondition'), 'condition'],
    'condition class mismatch' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(BaseCondition::class)
            ->condition(new TestElementCondition),
        'conditionClass',
    ],
    'invalid read-only state' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->condition(new TestElementCondition)
            ->readOnly(fn (): string => 'yes'),
        'readOnly',
    ],
]);
