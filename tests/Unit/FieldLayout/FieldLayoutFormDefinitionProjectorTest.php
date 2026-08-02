<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Group;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutFormElementProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutFormDefinitionContext;
use CraftCms\Cms\FieldLayout\FieldLayoutFormDefinitionProjector;
use CraftCms\Cms\FieldLayout\FieldLayoutFormElementContext;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;

it('projects applicable field layout content through provider form elements', function () {
    $layout = new FieldLayout(['uid' => 'article-layout']);
    $field = new ProjectableField([
        'uid' => 'body-field',
        'handle' => 'body',
        'name' => 'Body',
        'instructions' => 'Default body instructions',
    ]);
    $body = new NonEditableCustomField($field, [
        'uid' => 'body-layout-element',
        'label' => 'Article body',
        'instructions' => 'Override body instructions',
        'required' => true,
        'width' => 50,
    ]);
    $layout->setTabs([
        new FieldLayoutTab([
            'uid' => 'content-tab',
            'name' => 'Content',
            'layout' => $layout,
            'elements' => [$body, new InapplicableFormElement(['uid' => 'hidden-element'])],
        ]),
        new InapplicableFieldLayoutTab([
            'uid' => 'hidden-tab',
            'name' => 'Hidden',
            'layout' => $layout,
            'elements' => [new InapplicableFormElement(['uid' => 'hidden-tab-element'])],
        ]),
    ]);

    $projector = new FieldLayoutFormDefinitionProjector;
    $definition = $projector->project(
        $layout,
        new FieldLayoutFormDefinitionContext,
    )->toArray();
    $repeatedDefinition = $projector->project(
        $layout,
        new FieldLayoutFormDefinitionContext,
    )->toArray();

    expect($definition['elements'])->toHaveCount(1);

    $tabs = $definition['elements'][0];
    $tab = $tabs['children'][0];
    $fieldContainer = $tab['children'][0];
    $input = $fieldContainer['children'][0];

    expect($tabs)->toMatchArray([
        'type' => 'craft:tabs',
        'key' => 'article-layout',
    ])->and($tabs['children'])->toHaveCount(1)
        ->and($tab)->toMatchArray([
            'type' => 'craft:tab',
            'key' => 'content-tab',
            'props' => ['label' => 'Content'],
        ])->and($tab['children'])->toHaveCount(1)
        ->and($fieldContainer)->toMatchArray([
            'type' => 'craft:field',
            'key' => 'body-layout-element',
            'width' => 50,
            'props' => [
                'label' => 'Article body',
                'instructions' => 'Override body instructions',
                'readOnly' => true,
            ],
        ])->and($input)->toMatchArray([
            'type' => 'craft:text-input',
            'name' => 'fields.body',
            'props' => ['placeholder' => 'Projected body'],
        ])->and($repeatedDefinition)->toBe($definition)
        ->and($definition)->not->toHaveKey('bindingScope')
        ->and(json_encode($definition, JSON_THROW_ON_ERROR))->not->toContain(
            'userCondition',
            'elementCondition',
            'visibleWhen',
            'hidden-element',
            'hidden-tab',
        );
});

it('rejects provider components that are not inputs', function () {
    $layout = new FieldLayout(['uid' => 'invalid-provider-layout']);
    $layout->setTabs([
        new FieldLayoutTab([
            'uid' => 'content-tab',
            'name' => 'Content',
            'layout' => $layout,
            'elements' => [new ContainerProvidingLayoutElement(['uid' => 'invalid-provider'])],
        ]),
    ]);

    expect(fn () => new FieldLayoutFormDefinitionProjector()->project(
        $layout,
        new FieldLayoutFormDefinitionContext,
    ))->toThrow(LogicException::class, 'must provide a projectable input component');
});

it('fails loudly when an applicable layout element has no provider or adapter fallback', function () {
    $layout = new FieldLayout(['uid' => 'unsupported-layout']);
    $layout->setTabs([
        new FieldLayoutTab([
            'uid' => 'content-tab',
            'name' => 'Content',
            'layout' => $layout,
            'elements' => [new UnsupportedFormElement(['uid' => 'unsupported-element'])],
        ]),
    ]);

    expect(fn () => new FieldLayoutFormDefinitionProjector()->project(
        $layout,
        new FieldLayoutFormDefinitionContext(inputNamespace: 'entry'),
    ))->toThrow(LogicException::class, 'UnsupportedFormElement');
});

it('fails loudly when a projected tab has no stable source UID', function () {
    $layout = new FieldLayout(['uid' => 'unstable-layout']);
    $layout->setTabs([
        new FieldLayoutTab([
            'name' => 'Content',
            'layout' => $layout,
            'elements' => [new InapplicableFormElement],
        ]),
    ]);

    expect(fn () => new FieldLayoutFormDefinitionProjector()->project(
        $layout,
        new FieldLayoutFormDefinitionContext,
    ))->toThrow(LogicException::class, 'must have a UID');
});

it('fails loudly when a projected layout element has no stable source UID', function () {
    $layout = new FieldLayout(['uid' => 'unstable-layout']);
    $layout->setTabs([
        new FieldLayoutTab([
            'uid' => 'content-tab',
            'name' => 'Content',
            'layout' => $layout,
            'elements' => [new InapplicableFormElement],
        ]),
    ]);

    expect(fn () => new FieldLayoutFormDefinitionProjector()->project(
        $layout,
        new FieldLayoutFormDefinitionContext,
    ))->toThrow(LogicException::class, 'layout element must have a UID');
});

class ProjectableField extends Field implements FieldLayoutFormElementProviderInterface
{
    public function formElement(FieldLayoutFormElementContext $context): ?TextInput
    {
        return TextInput::make()
            ->name($context->inputName ?? throw new LogicException('Input Name is required.'))
            ->placeholder('Projected body');
    }
}

class NonEditableCustomField extends CustomField
{
    #[Override]
    public function editable(?ElementInterface $element): bool
    {
        return false;
    }
}

class InapplicableFieldLayoutTab extends FieldLayoutTab
{
    #[Override]
    public function showInForm(?ElementInterface $element = null): bool
    {
        return false;
    }
}

class InapplicableFormElement extends FieldLayoutElement implements FieldLayoutFormElementProviderInterface
{
    public function selectorHtml(): string
    {
        return '';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    #[Override]
    public function showInForm(?ElementInterface $element = null): bool
    {
        return false;
    }

    public function formElement(FieldLayoutFormElementContext $context): ?TextInput
    {
        return TextInput::make()->name('hidden');
    }
}

class UnsupportedFormElement extends FieldLayoutElement
{
    public function selectorHtml(): string
    {
        return '';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return '<input name="legacy">';
    }
}

class ContainerProvidingLayoutElement extends FieldLayoutElement implements FieldLayoutFormElementProviderInterface
{
    public function selectorHtml(): string
    {
        return '';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    public function formElement(FieldLayoutFormElementContext $context): ?Group
    {
        return Group::make([]);
    }
}
