<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\TableCells\MissingTableCell;
use CraftCms\Cms\Field\TableCells\TableCell;
use CraftCms\Cms\Field\TableCells\TableCellContext;
use CraftCms\Cms\Field\TableCellTypes;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Support\Facades\I18N;
use GraphQL\Type\Definition\Type;
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

it('resolves normalized cell values for their form inputs', function (array $column, mixed $value, mixed $expected) {
    $field = new Table([
        'handle' => 'details',
        'columns' => ['col1' => ['heading' => 'Value', 'handle' => 'value', ...$column]],
    ]);
    $rows = $field->normalizeValue([['col1' => $value]], null);
    $control = $field->formControl(new FieldContext('details', value: $rows));
    $payload = app(FormResolver::class)->resolve(Form::make([
        FormField::make('Details', $control),
    ]), new FormContext);

    expect($payload->values['details'][0]['col1'])->toBe($expected);
})->with([
    'date input' => [['type' => 'date'], '2026-04-17T13:45:00+00:00', '2026-04-17'],
    'time input in site timezone' => [['type' => 'time'], '2026-04-17T13:45:00+00:00', '06:45'],
    'normalized colour' => [['type' => 'color'], '#ABC', '#aabbcc'],
    'configured select default' => [[
        'type' => 'select',
        'options' => [['label' => 'Draft', 'value' => 'draft'], ['label' => 'Published', 'value' => 'published', 'default' => true]],
    ], null, 'published'],
    'grouped select default' => [[
        'type' => 'select',
        'options' => [['label' => 'Editorial', 'options' => [['label' => 'Draft', 'value' => 'draft'], ['label' => 'Review', 'value' => 'review', 'default' => true]]]],
    ], null, 'review'],
    'first select option' => [[
        'type' => 'select',
        'options' => [['label' => 'Draft', 'value' => 'draft'], ['label' => 'Published', 'value' => 'published']],
    ], null, 'draft'],
    'explicit empty select value' => [[
        'type' => 'select',
        'options' => [['label' => 'Draft', 'value' => 'draft', 'default' => true]],
    ], '', ''],
]);

it('validates optional built-in cell values and reports their table paths', function (string $type, mixed $value, bool $valid) {
    $field = new Table([
        'handle' => 'details',
        'columns' => ['col1' => ['heading' => 'Value', 'handle' => 'value', 'type' => $type]],
    ]);
    $rows = $field->normalizeValue([['col1' => $value]], null);
    $errors = [];

    $field->validateTableData($rows, function (string $path, string $message) use (&$errors): void {
        $errors[$path][] = $message;
    }, 'details');

    expect(array_keys($errors))->toBe($valid ? [] : ['details.0.col1', 'details']);
})->with([
    'valid URL' => ['url', ' https://example.com/page ', true],
    'invalid URL' => ['url', 'invalid', false],
    'empty URL' => ['url', '', true],
    'null URL' => ['url', null, true],
    'valid email' => ['email', ' author@example.com ', true],
    'invalid email' => ['email', 'invalid', false],
    'empty email' => ['email', '', true],
    'null email' => ['email', null, true],
    'normalized colour' => ['color', '#ABC', true],
    'invalid colour' => ['color', '#gggggg', false],
    'empty colour' => ['color', '', true],
    'null colour' => ['color', null, true],
    'valid money' => ['money', ['value' => '12,50', 'locale' => 'nl-BE'], true],
    'invalid money' => ['money', ['value' => 'invalid', 'locale' => 'nl-BE'], false],
    'empty money' => ['money', ['value' => '', 'locale' => 'nl-BE'], true],
]);

it('round-trips money cells through localized row forms and scalar storage', function (?string $amount, ?string $expected) {
    I18N::withLocale('en-US', 'nl-BE', function () use ($amount, $expected): void {
        $field = new Table([
            'name' => 'Prices',
            'handle' => 'prices',
            'columns' => ['amount' => [
                'heading' => 'Amount', 'handle' => 'price', 'type' => 'money',
                'currency' => 'EUR', 'showCurrency' => false,
            ]],
        ]);
        expect($field->validate())->toBeTrue();

        $normalized = $field->normalizeValueFromRequest([['amount' => ['value' => $amount, 'locale' => 'nl-BE']]], null);
        $stored = $field->serializeValueForDb($normalized, new Entry);
        $payload = app(FormResolver::class)->resolve(Form::make([
            FormField::make('Prices', $field->formControl(new FieldContext('prices', value: $field->normalizeValue($stored, null)))),
        ]), new FormContext);
        $cell = $payload->nodes[0]->control->forms[0]->nodes[0]->control;

        expect($stored)->toBe([['amount' => $expected]])
            ->and($payload->values['prices'][0]['amount'])->toBe(['value' => $amount === '' ? null : $amount, 'locale' => 'nl-BE'])
            ->and($cell->component)->toBe('craft:money')
            ->and($cell->path)->toBe(['prices', '0', 'amount'])
            ->and($cell->props)->toMatchArray(['currency' => 'EUR', 'locale' => 'nl-BE', 'showCurrency' => false]);

        $settings = app(FormResolver::class)->resolve($field->settingsForm(), new FormContext);
        $types = array_column($settings->nodes[0]->control->props['cellTypes'], 'value');
        expect($types)->toContain('money')
            ->and(TableRow::prepareRowFieldDefinition($field->columns)['price']->name)->toBe('Number');
    });
})->with([
    'amount' => ['12,50', '12.50'],
    'zero' => ['0', '0'],
    'cleared' => ['', null],
    'new' => [null, null],
]);

it('rejects unavailable currencies in money column settings', function () {
    $field = new Table([
        'name' => 'Prices',
        'handle' => 'prices',
        'columns' => ['amount' => ['heading' => 'Amount', 'handle' => 'price', 'type' => 'money', 'currency' => 'XYZ']],
    ]);

    expect($field->validate())->toBeFalse()
        ->and($field->errors()->has('columns.amount.currency'))->toBeTrue();
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
        ->and($rerenderedPayload->nodes[0]->control->props['errors'])->toBe($payload->nodes[0]->control->props['errors']);

    $mountedPayload = json_decode($crawler->filter('craft-table-form')->first()->attr('data-payload'), true);
    expect($mountedPayload['values']['columns']['first']['handle'])->toBe('invalid-handle')
        ->and($mountedPayload['nodes'][0]['control']['props']['errors']['first'])->toBe(['handle' => true]);

    $columns = $field->columns;
    $columns['first']['handle'] = 'firstHandle';
    $columns['third']['handle'] = 'thirdHandle';
    $field = new Table(['name' => 'Details', 'handle' => 'details', 'columns' => $columns]);

    expect($field->validate())->toBeTrue();

    $payload = app(FormResolver::class)->resolve($field->settingsForm(), new FormContext);
    expect($payload->values['columns']['first']['handle'])->toBe('firstHandle')
        ->and($payload->values['columns']['third']['handle'])->toBe('thirdHandle')
        ->and($payload->nodes[0]->control->props)->not->toHaveKey('errors');
});

it('uses registered cell settings for value conversion, validation, and GraphQL field types', function () {
    app(TableCellTypes::class)->register(ScaledTableCell::class);
    $field = new Table([
        'handle' => 'details',
        'columns' => [
            'col1' => ['heading' => 'Quantity', 'handle' => 'quantity', 'type' => ScaledTableCell::class, 'factor' => 10],
        ],
    ]);

    $normalized = $field->normalizeValueFromRequest([['quantity' => 25]], null);
    $errors = [];
    $field->validateTableData($normalized, function (string $error) use (&$errors): void {
        $errors[] = $error;
    });
    $stored = $field->serializeValueForDb($normalized, new Entry);

    expect($normalized)->toBe([['quantity' => 2.5, 'col1' => 2.5]])
        ->and($errors)->toBe([])
        ->and($stored)->toBe([['col1' => 25.0]])
        ->and($field->normalizeValue($stored, null))->toBe([['col1' => 2.5, 'quantity' => 2.5]])
        ->and(TableRow::prepareRowFieldDefinition($field->columns)['quantity'])->toBe(Type::float());

    $field->validateTableData([['col1' => -1]], function (string $error) use (&$errors): void {
        $errors[] = $error;
    });
    expect($errors)->toBe(['Quantity must be positive.']);
});

it('preserves unavailable cell settings and raw content until its provider returns', function () {
    $column = ['heading' => 'Quantity', 'handle' => 'quantity', 'type' => ScaledTableCell::class, 'factor' => 10];
    $field = new Table(['handle' => 'details', 'columns' => ['col1' => $column]]);
    app(TableCellTypes::class)->remove(ScaledTableCell::class);
    $raw = [['col1' => 25, 'rowId' => 'static-row', 'legacy' => ['opaque' => true]]];
    $element = new class extends Entry
    {
        public Table $table;

        protected function fieldByHandle(string $handle): FieldInterface
        {
            return $this->table;
        }
    };
    $element->table = $field;
    $element->setFieldValue('details', $raw);

    expect($field->cellType($column))->toBeInstanceOf(MissingTableCell::class)
        ->and($field->cellType($column)->getSettings())->toBe(['factor' => 10])
        ->and($field->normalizeValue($raw, null))->toBe($raw)
        ->and($field->serializeValueForDb($raw, $element))->toBe($raw)
        ->and($field->normalizeValueFromRequest([['col1' => 'tampered']], $element))->toBe($raw);

    app(TableCellTypes::class)->register(ScaledTableCell::class);
    expect($field->normalizeValue($raw, null)[0]['quantity'])->toBe(2.5);
});

it('rejects unregistered cell types submitted as new column settings', function () {
    app(TableCellTypes::class)->remove(ScaledTableCell::class);
    $field = new Table([
        'name' => 'Details',
        'handle' => 'details',
        'columns' => ['col1' => ['heading' => 'Quantity', 'handle' => 'quantity', 'type' => ScaledTableCell::class]],
    ]);

    expect($field->validate())->toBeFalse()
        ->and($field->columns['col1']['type'])->toBe(ScaledTableCell::class);
});

class ScaledTableCell extends TableCell
{
    public int $factor = 1;

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        return $value === null ? null : $value / $this->factor;
    }

    public function serializeValue(mixed $value, bool $forDb = false): bool|float|int|string|null
    {
        return $value === null ? null : $value * $this->factor;
    }

    public function formControl(TableCellContext $context): Control
    {
        return Number::make($context->path)->value($this->serializeValue($context->value));
    }

    public function getValueRules(): array
    {
        return ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
            if ($value < 0) {
                $fail('Quantity must be positive.');
            }
        }];
    }

    public function gqlType(): Type
    {
        return Type::float();
    }
}
