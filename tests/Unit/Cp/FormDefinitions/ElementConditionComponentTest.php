<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\ElementCondition;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;

class ProjectableElementCondition extends BaseCondition
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
    $condition = new ProjectableElementCondition([
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
        ->toContainTag('craft-element-condition', [
            'id' => 'selection-condition',
            'class' => 'condition-container',
            'readonly' => true,
        ])
        ->toContainTag('input', [
            'name' => 'selectionCondition[class]',
            'value' => ProjectableElementCondition::class,
        ])
        ->and($condition->name)->toBe('condition')
        ->and($condition->mainTag)->toBe('form')
        ->and($condition->forProjectConfig)->toBeFalse();
});

it('registers and deterministically projects serializable condition editor configuration', function () {
    $component = ElementCondition::make()
        ->name('selectionCondition')
        ->conditionClass(ProjectableElementCondition::class)
        ->builderConfig(['scope' => 'entries'])
        ->sortable(false)
        ->addRuleLabel('Add condition')
        ->attributes(['data' => ['setting' => 'selection-condition']]);
    $definition = FormDefinition::make([
        Field::make($component),
    ]);
    $expected = [
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:element-condition-input',
                'name' => 'selectionCondition',
                'props' => [
                    'conditionClass' => ProjectableElementCondition::class,
                    'builderConfig' => ['scope' => 'entries'],
                    'sortable' => false,
                    'addRuleLabel' => 'Add condition',
                ],
                'attributes' => ['data' => ['setting' => 'selection-condition']],
            ]],
        ]],
    ];
    $firstProjection = $definition->toArray();

    expect(app(ComponentRegistry::class)->make('element-condition'))->toBeInstanceOf(ElementCondition::class)
        ->and(app(FormElementTypes::class)->isRegistered(ElementCondition::formElementType()))->toBeTrue()
        ->and($firstProjection)->toBe($expected)
        ->and($definition->toArray())->toBe($firstProjection);
});

it('rejects host-owned condition editor state during projection', function (ElementCondition $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', ElementCondition::class, $option),
    );
})->with([
    'executable condition' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(ProjectableElementCondition::class)
            ->condition(new ProjectableElementCondition),
        'condition',
    ],
    'read-only authorization state' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(ProjectableElementCondition::class)
            ->readOnly(false),
        'readOnly',
    ],
]);

it('rejects invalid portable condition editor configuration', function (ElementCondition $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', ElementCondition::class, $option),
    );
})->with([
    'name' => [fn () => ElementCondition::make()->conditionClass(ProjectableElementCondition::class), 'name'],
    'condition class' => [fn () => ElementCondition::make()->name('selectionCondition')->conditionClass(stdClass::class), 'conditionClass'],
    'builder config' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(ProjectableElementCondition::class)
            ->builderConfig(['condition' => new stdClass]),
        'builderConfig.condition',
    ],
    'sortable state' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(ProjectableElementCondition::class)
            ->sortable(fn (): string => 'yes'),
        'sortable',
    ],
    'add rule label' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(ProjectableElementCondition::class)
            ->addRuleLabel(fn (): int => 1),
        'addRuleLabel',
    ],
    'host-owned attribute' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->conditionClass(ProjectableElementCondition::class)
            ->attributes(['id' => 'selection-condition']),
        'attributes.id',
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
            ->condition(new ProjectableElementCondition),
        'conditionClass',
    ],
    'invalid read-only state' => [
        fn () => ElementCondition::make()
            ->name('selectionCondition')
            ->condition(new ProjectableElementCondition)
            ->readOnly(fn (): string => 'yes'),
        'readOnly',
    ],
]);
