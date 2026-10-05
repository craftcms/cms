<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\TableColumns;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;

it('resolves table cells at concrete paths while keeping row templates outside values and errors', function () {
    $form = Form::make([
        Field::make('Rows', Table::make('rows')->columns([
            'name' => ['heading' => 'Name', 'control' => Text::make('ignored')->placeholder('Enter name')],
        ])->value([['name' => 'Ada']])->defaultValues(['name' => 'New row'])->allowAdd()),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        errors: ['rows.0.name' => 'A real cell error.', 'rows.__template__.name' => 'An unbound row error.'],
    ));
    $table = $payload->nodes[0]->control;

    expect($payload->values)->toBe(['settings' => ['rows' => [['name' => 'Ada']]]])
        ->and($table->forms[0]->scope)->toBe(['settings', 'rows', '0'])
        ->and($table->forms[0]->nodes[0]->control->path)->toBe(['settings', 'rows', '0', 'name'])
        ->and($table->forms[0]->nodes[0]->control->deltaGroup)->toBe(['settings', 'rows'])
        ->and($table->props['rowTemplate']['nodes'][0]['control']['path'])->toBe(['name'])
        ->and($table->props['rowTemplate']['nodes'][0]['control']['props']['placeholder'])->toBe('Enter name')
        ->and($payload->errors)->toBe([
            ['path' => ['settings', 'rows', '0', 'name'], 'messages' => ['A real cell error.']],
            ['path' => ['settings', 'rows'], 'messages' => ['An unbound row error.']],
        ]);
});

it('applies table modes to concrete cells and reusable row templates', function (ControlMode $mode) {
    $form = Form::make([
        Field::make('Rows', Table::make('rows')->columns([
            'name' => ['heading' => 'Name', 'type' => 'singleline'],
        ])->value([['name' => 'Ada']])->mode($mode)),
    ]);
    $table = app(FormResolver::class)->resolve($form, new FormContext)->nodes[0]->control;

    expect($table->forms[0]->nodes[0]->control->mode)->toBe($mode)
        ->and($table->props['rowTemplate']['nodes'][0]['control']['mode'])->toBe($mode->value);
})->with([ControlMode::ReadOnly, ControlMode::Disabled]);

it('resolves column controls for an empty editor without adding a column to submitted values', function () {
    $form = Form::make([
        Field::make('Columns', TableColumns::make('columns')
            ->cellTypes([['label' => 'Text', 'value' => 'singleline']])
            ->value([])->mode(ControlMode::ReadOnly)),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(namespace: 'settings'));
    $control = $payload->nodes[0]->control;

    expect($payload->values)->toBe(['settings' => ['columns' => []]])
        ->and($control->forms)->toBe([])
        ->and($control->props['rowTemplate']['nodes'][0]['control']['path'])->toBe(['heading'])
        ->and($control->props['rowTemplate']['nodes'][0]['control']['mode'])->toBe(ControlMode::ReadOnly->value);
});

it('rejects unregistered controls in an empty table row template', function () {
    $form = Form::make([
        Field::make('Rows', Table::make('rows')->columns([
            'name' => ['control' => TableUnregisteredControl::make('name')],
        ])->value([])),
    ]);

    expect(fn () => app(FormResolver::class)->resolve($form, new FormContext))
        ->toThrow(InvalidArgumentException::class, 'is not registered');
});

class TableUnregisteredControl extends Text {}
