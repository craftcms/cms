<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\TableColumns;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use Symfony\Component\DomCrawler\Crawler;

it('resolves table cells at concrete paths while keeping row templates outside values and errors', function () {
    $form = Form::make([
        Field::make('Rows', Table::make('rows')->columns([
            'name' => ['heading' => 'Name', 'control' => Text::make('ignored')->placeholder('Enter name')],
        ])->value([['name' => 'Ada']])->defaultValues(['name' => 'New row'])->allowAdd()->hiddenRows(['0'])),
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
        ->and($table->props['hiddenRows'])->toBe(['0'])
        ->and($payload->errors)->toBe([
            ['path' => ['settings', 'rows', '0', 'name'], 'messages' => ['A real cell error.']],
            ['path' => ['settings', 'rows'], 'messages' => ['An unbound row error.']],
        ]);
});

it('resolves money columns in reusable tables with their row input paths', function () {
    $payload = app(FormResolver::class)->resolve(Form::make([
        Field::make('Prices', Table::make('prices')->columns([
            'amount' => ['heading' => 'Amount', 'type' => 'money', 'currency' => 'EUR', 'locale' => 'nl-BE', 'showCurrency' => false],
        ])->value([['amount' => '']])->allowAdd()),
    ]), new FormContext(namespace: 'settings'));
    $cell = $payload->nodes[0]->control->forms[0]->nodes[0]->control;

    expect($cell->component)->toBe('craft:money')
        ->and($cell->path)->toBe(['settings', 'prices', '0', 'amount'])
        ->and($cell->props)->toMatchArray(['currency' => 'EUR', 'locale' => 'nl-BE', 'showCurrency' => false])
        ->and($payload->nodes[0]->control->props['rowTemplate']['nodes'][0]['control']['component'])->toBe('craft:money');
});

it('preserves nested and flat groups in reusable table select columns', function () {
    $payload = app(FormResolver::class)->resolve(Form::make([
        Field::make('Rows', Table::make('rows')->columns([
            'status' => ['heading' => 'Status', 'type' => 'select', 'options' => [
                ['label' => 'Editorial', 'options' => [['label' => 'Draft', 'value' => 'draft'], ['label' => 'Review', 'value' => 'review']]],
                ['label' => 'Published', 'value' => 'published', 'group' => 'Public'],
                ['label' => 'Archived', 'value' => 'archived'],
            ]],
        ])->value([['status' => 'review']])),
    ]), new FormContext);
    $cell = $payload->nodes[0]->control->forms[0]->nodes[0]->control;
    $crawler = new Crawler(app(FormHtmlRenderer::class)->renderControl($cell, $payload->values, 'status', false, false));

    expect($cell->props['options'])->toEqual([
        ['label' => 'Draft', 'value' => 'draft', 'group' => 'Editorial'],
        ['label' => 'Review', 'value' => 'review', 'group' => 'Editorial'],
        ['label' => 'Published', 'value' => 'published', 'group' => 'Public'],
        ['label' => 'Archived', 'value' => 'archived'],
    ])
        ->and($crawler->filter('optgroup[label="Editorial"] option[selected]')->attr('value'))->toBe('review')
        ->and($crawler->filter('optgroup[label="Public"] option')->attr('value'))->toBe('published');
});

it('resolves implicit option groups for reusable table combobox columns', function () {
    $payload = app(FormResolver::class)->resolve(Form::make([
        Field::make('Rows', Table::make('rows')->columns([
            'address' => ['heading' => 'Address', 'type' => 'autosuggest', 'options' => [
                ['label' => 'Environment', 'options' => [['label' => 'System email', 'value' => '$SYSTEM_EMAIL'], ['value' => 0]]],
                ['label' => 'Literal address', 'value' => 'admin@example.com'],
            ]],
        ])->value([['address' => '$SYSTEM_EMAIL']])),
    ]), new FormContext);

    expect($payload->nodes[0]->control->forms[0]->nodes[0]->control->props['options'])->toEqual([
        ['type' => 'optgroup', 'label' => 'Environment', 'options' => [
            ['label' => 'System email', 'value' => '$SYSTEM_EMAIL'],
            ['label' => '0', 'value' => '0'],
        ]],
        ['label' => 'Literal address', 'value' => 'admin@example.com'],
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
