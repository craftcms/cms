<?php

declare(strict_types=1);

use CraftCms\Cms\Form\FormControlTypes;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormNodeTypes;
use Workbench\App\Forms\FormKitchenSink;

it('has a kitchen sink story for every registered form type', function () {
    expect(array_values(FormKitchenSink::COMPONENTS['controls']))
        ->toEqualCanonicalizing(app(FormControlTypes::class)->types()->all())
        ->and(array_values(FormKitchenSink::COMPONENTS['nodes']))
        ->toEqualCanonicalizing(app(FormNodeTypes::class)->types()->all());

    $kitchenSink = app(FormKitchenSink::class);
    $htmlRenderer = app(FormHtmlRenderer::class);

    foreach (FormKitchenSink::COMPONENTS as $type => $components) {
        foreach (array_keys($components) as $slug) {
            $stories = $kitchenSink->stories($type, $slug)
                ?? throw new LogicException("Missing stories for {$type}/{$slug}.");

            expect($stories)->not->toBeEmpty();

            if ($slug === 'grouped-entry-type-manager') {
                continue;
            }

            foreach ($stories as $story) {
                expect($htmlRenderer->render($story))->not->toBeEmpty();
            }
        }
    }

    $storySets = [
        'controls' => [
            'address' => ['Belgium', 'United States', 'Current user'],
            'choice' => ['Select', 'Multiple select', 'Checkboxes', 'Radios', 'Buttons', 'Multiple buttons'],
            'color' => ['Picker', 'Presets'],
            'combobox' => ['Selected', 'Placeholder'],
            'condition-builder' => ['Default', 'Project config'],
            'content-block' => ['Empty', 'Populated'],
            'date' => ['Default', 'Constrained'],
            'date-time' => ['Date', 'Time', 'Date and time', 'With time zone'],
            'element-select' => ['Single', 'Multiple'],
            'field-layout-designer' => ['Customizable tabs', 'Fixed tabs'],
            'icon-picker' => ['Default', 'Free only'],
            'lightswitch' => ['Off', 'On', 'Indeterminate', 'Small', 'Labels'],
            'link' => ['Basic', 'Label and advanced fields'],
            'markdown' => ['Toolbar', 'No toolbar'],
            'matrix' => ['Empty', 'Populated', 'Entry limits'],
            'money' => ['Currency', 'Without currency'],
            'number' => ['Default', 'Constrained'],
            'range' => ['Default', 'Stepped'],
            'table' => ['Fixed rows', 'Editable rows', 'Keyed rows'],
            'text' => ['Default', 'Email', 'Password', 'Monospace', 'Feedback'],
            'textarea' => ['Default', 'Monospace'],
            'time' => ['Default', '15-minute steps'],
        ],
        'nodes' => [
            'callout' => ['Info', 'Success', 'Warning', 'Danger', 'Dismissible'],
            'field' => ['Default', 'Required', 'Instructions after', 'Feedback'],
            'group' => ['Fieldset', 'Collapsible'],
            'markdown-content' => ['Pane', 'Plain'],
        ],
    ];

    foreach ($storySets as $type => $components) {
        foreach ($components as $slug => $names) {
            $stories = $kitchenSink->stories($type, $slug)
                ?? throw new LogicException("Missing stories for {$type}/{$slug}.");

            expect(array_keys($stories))->toBe($names)
                ->and(collect($stories)->pluck('scope')->unique())
                ->toHaveCount(count($names));
        }
    }
});
