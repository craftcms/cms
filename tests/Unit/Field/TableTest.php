<?php

declare(strict_types=1);

use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use Symfony\Component\DomCrawler\Crawler;

it('uses configured default row values for newly added rows', function () {
    $field = new Table([
        'columns' => [
            'label' => ['heading' => 'Label', 'handle' => 'label', 'type' => 'singleline'],
            'enabled' => ['heading' => 'Enabled', 'handle' => 'enabled', 'type' => 'lightswitch'],
        ],
        'defaultRowValues' => [[
            'label' => 'New row',
            'enabled' => true,
        ]],
    ]);

    $settings = app(FormResolver::class)->resolve($field->settingsForm(), new FormContext);
    $defaultRowValues = $settings->nodes[1]->children[1];

    expect($defaultRowValues->props['label'])->toBe('Default Row Values')
        ->and($settings->values['defaultRowValues'])->toBe([[
            'label' => 'New row',
            'enabled' => true,
        ]])
        ->and($defaultRowValues->control->props)->toMatchArray([
            'minRows' => 1,
            'maxRows' => 1,
        ])
        ->and($field->formControl(new FieldContext('details'))->props()['defaultValues'])->toBe([
            'label' => 'New row',
            'enabled' => true,
        ]);
});

it('configures single-line defaults for new columns', function () {
    $field = new Table(['name' => 'Details', 'handle' => 'details']);

    $payload = app(FormResolver::class)->resolve($field->settingsForm(), new FormContext);
    $columns = $payload->nodes[0]->control;

    expect($columns->props['defaultValues'])->toBe(['type' => 'singleline']);
});

it('keeps column handles scalar while rendering validation errors by cell', function () {
    $field = new Table([
        'name' => 'Details',
        'handle' => 'details',
        'columns' => [
            'first' => ['heading' => 'First', 'handle' => 'invalid-handle', 'type' => 'singleline'],
            'second' => ['heading' => 'Second', 'handle' => 'validHandle', 'type' => 'singleline'],
            'third' => ['heading' => 'Third', 'handle' => 'col3', 'type' => 'singleline'],
        ],
    ]);

    expect($field->columns['first']['handle'])->toBeString()
        ->and($field->columns['second']['handle'])->toBeString()
        ->and($field->columns['third']['handle'])->toBeString()
        ->and($field->validate())->toBeFalse()
        ->and($field->columns['first']['handle'])->toBe('invalid-handle')
        ->and($field->columns['second']['handle'])->toBe('validHandle')
        ->and($field->columns['third']['handle'])->toBe('col3')
        ->and($field->errors()->get('columns'))->toHaveCount(2);

    $context = new FormContext(errors: $field->errors()->getMessages());
    $payload = app(FormResolver::class)->resolve($field->settingsForm($context), $context);
    $rerenderedPayload = app(FormResolver::class)->resolve($field->settingsForm($context), $context);
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($payload->values['columns']['first']['handle'])->toBe('invalid-handle')
        ->and($payload->values['columns']['second']['handle'])->toBe('validHandle')
        ->and($payload->values['columns']['third']['handle'])->toBe('col3')
        ->and($payload->nodes[0]->control->props['errors'])->toBe([
            'first' => ['handle' => true],
            'third' => ['handle' => true],
        ])
        ->and($rerenderedPayload->values)->toBe($payload->values)
        ->and($rerenderedPayload->nodes[0]->control->props['errors'])->toBe($payload->nodes[0]->control->props['errors'])
        ->and($crawler->filter('td.error textarea[name="columns[first][handle]"]')->text())->toBe('invalid-handle')
        ->and($crawler->filter('td.error textarea[name="columns[second][handle]"]'))->toHaveCount(0)
        ->and($crawler->filter('td.error textarea[name="columns[third][handle]"]')->text())->toBe('col3');

    $columns = $field->columns;
    $columns['first']['handle'] = 'firstHandle';
    $columns['third']['handle'] = 'thirdHandle';
    $field = new Table(['name' => 'Details', 'handle' => 'details', 'columns' => $columns]);

    expect($field->validate())->toBeTrue();

    $payload = app(FormResolver::class)->resolve($field->settingsForm(), new FormContext);
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($payload->values['columns']['first']['handle'])->toBe('firstHandle')
        ->and($payload->values['columns']['third']['handle'])->toBe('thirdHandle')
        ->and($payload->nodes[0]->control->props)->not->toHaveKey('errors')
        ->and($crawler->filter('td.error'))->toHaveCount(0);
});
