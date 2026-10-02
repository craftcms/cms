<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\FieldLayout\LayoutElements\LineBreak;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\FieldLayout\LayoutElements\Template;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Json;

function settingsContext(): FormContext
{
    return new FormContext(namespace: 'settings', refreshable: true);
}

function attachedTo(FieldLayoutComponent $component): FieldLayoutComponent
{
    $layout = new FieldLayout;
    $layout->type = Entry::class;
    $component->setLayout($layout);
    $component->elementType = Entry::class;

    return $component;
}

it('resolves, encodes and renders settings for every layout component', function (FieldLayoutComponent $component) {
    $context = settingsContext();
    $form = $component->settingsForm($context);

    expect($form)->not->toBeNull();

    $payload = app(FormResolver::class)->resolve($form, $context);

    expect($payload->scope)->toBe(['settings'])
        ->and(Json::encode($payload))->toBeString()
        ->and(app(FormHtmlRenderer::class)->render($payload))->toBeString()->not->toBe('');
})->with([
    'tab' => fn () => attachedTo(new FieldLayoutTab(['name' => 'Content', 'uid' => 'tab-uid'])),
    'heading' => fn () => attachedTo(new Heading(['heading' => 'Hi', 'uid' => 'heading-uid'])),
    'markdown' => fn () => attachedTo(new Markdown(['content' => '# Hi', 'uid' => 'md-uid'])),
    'tip' => fn () => attachedTo(new Tip(['tip' => 'Careful.', 'uid' => 'tip-uid'])),
    'template' => fn () => attachedTo(new Template(['template' => '_foo', 'uid' => 'tpl-uid'])),
    'horizontal rule' => fn () => attachedTo(new HorizontalRule(['uid' => 'hr-uid'])),
    'line break' => fn () => attachedTo(new LineBreak(['uid' => 'br-uid'])),
]);

it('separates settings from conditions, and omits the separator when there are no settings', function () {
    $withSettings = attachedTo(new Heading(['heading' => 'Hi', 'uid' => 'heading-uid']));
    $conditionsOnly = attachedTo(new HorizontalRule(['uid' => 'hr-uid']));

    $withSettingsNodes = app(FormResolver::class)
        ->resolve($withSettings->settingsForm(settingsContext()), settingsContext())
        ->nodes;
    $conditionsOnlyNodes = app(FormResolver::class)
        ->resolve($conditionsOnly->settingsForm(settingsContext()), settingsContext())
        ->nodes;

    $separators = fn (array $nodes) => array_values(array_filter(
        $nodes,
        fn ($node): bool => $node->component === 'craft:separator',
    ));

    expect($separators($withSettingsNodes))->toHaveCount(1)
        ->and($separators($conditionsOnlyNodes))->toHaveCount(0)
        ->and($conditionsOnlyNodes[0]->uid)->toBe('visibility-conditions');
});

it('treats a classless condition config as no condition', function () {
    // ConditionBuilder Controls post `[]` for an empty condition, and
    // getElementCondition() merges `fieldLayouts` into it before normalizing.
    $component = attachedTo(new Heading(['heading' => 'Hi', 'uid' => 'heading-uid']));
    $component->setUserCondition([]);
    $component->setElementCondition([]);

    expect($component->getUserCondition())->toBeNull()
        ->and($component->getElementCondition())->toBeNull();

    $payload = app(FormResolver::class)->resolve(
        $component->settingsForm(settingsContext()),
        settingsContext(),
    );

    expect($payload->nodes)->not->toBeEmpty();
});

it('builds visibility condition controls at the expected paths', function () {
    $component = attachedTo(new Heading(['heading' => 'Hi', 'uid' => 'heading-uid']));
    $payload = app(FormResolver::class)->resolve(
        $component->settingsForm(settingsContext()),
        settingsContext(),
    );

    $group = collect($payload->nodes)->firstWhere('uid', 'visibility-conditions');
    $paths = collect($group->children)->map(fn ($child) => $child->control->path)->all();

    expect($paths)->toBe([
        ['settings', 'userCondition'],
        ['settings', 'elementCondition'],
    ]);
});

it('marks the hide-label action as reactive independently of the label', function () {
    $context = settingsContext();
    $component = attachedTo(new EntryTitleField(['uid' => 'title-field']));
    $payload = app(FormResolver::class)->resolve($component->settingsForm($context), $context);
    $label = $payload->nodes[0];

    expect($label->control->reactive)->toBeTrue()
        ->and($label->children[0]->control->reactive)->toBeTrue();
});
