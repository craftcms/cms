<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\TableCells\MissingTableCell;
use CraftCms\Cms\Field\TableCells\TableCell;
use CraftCms\Cms\Field\TableCells\TableCellContext;
use CraftCms\Cms\Field\TableCellTypes;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
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

    $settings = app(UiResolver::class)->resolve($field->settingsUi(), new UiContext);
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
        ->and($field->uiControl(new FieldContext('details'))->props()['defaultValues'])->toBe([
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
    $control = $field->uiControl(new FieldContext('details', value: $rows));
    $payload = app(UiResolver::class)->resolve(Ui::make([
        UiField::make('Details', $control),
    ]), new UiContext);

    expect($payload->values['details'][0]['col1'])->toBe($expected);
})->with([
    'date input' => [['type' => 'date'], '2026-04-17T13:45:00+00:00', '2026-04-17'],
    'time input in site timezone' => [['type' => 'time'], '2026-04-17T13:45:00+00:00', '06:45'],
    'normalized colour' => [['type' => 'color'], '#ABC', '#aabbcc'],
    'single-line text without line breaks' => [['type' => 'singleline'], "First\r\nsecond\nthird", 'First second third'],
    'multi-line text with line breaks' => [['type' => 'multiline'], "First\r\nsecond", "First\nsecond"],
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

it('edits single-line cells in one-row textareas that keep their values on one line', function () {
    $field = new Table([
        'handle' => 'details',
        'columns' => ['col1' => ['heading' => 'Value', 'handle' => 'value', 'type' => 'singleline']],
    ]);
    $control = $field->uiControl(new FieldContext('details', value: $field->normalizeValue([['col1' => 'Ada']], null)));
    $payload = app(UiResolver::class)->resolve(Ui::make([
        UiField::make('Details', $control),
    ]), new UiContext);
    $cell = $payload->nodes[0]->control->uis[0]->nodes[0]->control;

    expect($cell->component)->toBe('craft:textarea')
        ->and($cell->props)->toMatchArray(['rows' => 1, 'singleLine' => true]);
});

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
        $payload = app(UiResolver::class)->resolve(Ui::make([
            UiField::make('Prices', $field->uiControl(new FieldContext('prices', value: $field->normalizeValue($stored, null)))),
        ]), new UiContext);
        $cell = $payload->nodes[0]->control->uis[0]->nodes[0]->control;

        expect($stored)->toBe([['amount' => $expected]])
            ->and($payload->values['prices'][0]['amount'])->toBe(['value' => $amount === '' ? null : $amount, 'locale' => 'nl-BE'])
            ->and($cell->component)->toBe('craft:money')
            ->and($cell->path)->toBe(['prices', '0', 'amount'])
            ->and($cell->props)->toMatchArray(['currency' => 'EUR', 'locale' => 'nl-BE', 'showCurrency' => false]);

        $settings = app(UiResolver::class)->resolve($field->settingsUi(), new UiContext);
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

it('names link column settings by their labels in validation errors', function () {
    $field = new Table([
        'name' => 'Resources',
        'handle' => 'resources',
        'columns' => ['col1' => ['heading' => 'Link', 'handle' => 'link', 'type' => 'link', 'types' => []]],
    ]);

    expect($field->validate())->toBeFalse()
        ->and($field->errors()->get('columns.col1.types'))->toBe(['The Allowed Link Types field is required.']);
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

    $context = new UiContext(errors: $field->errors()->getMessages());
    $payload = app(UiResolver::class)->resolve($field->settingsUi($context), $context);
    $rerenderedPayload = app(UiResolver::class)->resolve($field->settingsUi($context), $context);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($payload->values['columns']['first']['handle'])->toBe('invalid-handle')
        ->and($payload->values['columns']['second']['handle'])->toBe('validHandle')
        ->and($payload->values['columns']['third']['handle'])->toBe('col3')
        ->and($payload->nodes[0]->control->props['errors'])->toBe([
            'first' => ['handle' => true],
            'third' => ['handle' => true],
        ])
        ->and($rerenderedPayload->values)->toBe($payload->values)
        ->and($rerenderedPayload->nodes[0]->control->props['errors'])->toBe($payload->nodes[0]->control->props['errors']);

    $mountedPayload = json_decode($crawler->filter('craft-table-ui')->first()->attr('data-payload'), true);
    expect($mountedPayload['values']['columns']['first']['handle'])->toBe('invalid-handle')
        ->and($mountedPayload['nodes'][0]['control']['props']['errors']['first'])->toBe(['handle' => true]);

    $columns = $field->columns;
    $columns['first']['handle'] = 'firstHandle';
    $columns['third']['handle'] = 'thirdHandle';
    $field = new Table(['name' => 'Details', 'handle' => 'details', 'columns' => $columns]);

    expect($field->validate())->toBeTrue();

    $payload = app(UiResolver::class)->resolve($field->settingsUi(), new UiContext);
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

it('renders registered cells and their validation feedback in inline HTML inputs', function () {
    app(TableCellTypes::class)->register(ScaledTableCell::class);
    $field = new Table([
        'handle' => 'details',
        'columns' => [
            'col1' => ['heading' => 'Quantity', 'handle' => 'quantity', 'type' => ScaledTableCell::class, 'factor' => 10],
        ],
    ]);
    $element = new class extends Entry
    {
        public function getLanguage(): string
        {
            return 'en-US';
        }
    };
    $element->errors()->add('details.0.col1', 'Quantity must be positive.');
    $element->errors()->add('title', 'Unrelated title error.');
    $rows = $field->normalizeValue([['col1' => -25]], $element);
    $html = $field->getInlineInputHtml($rows, $element);
    $input = new Crawler($html);
    $payload = json_decode($input->filter('craft-table-ui')->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);

    expect($input->filter('craft-table-ui')->attr('name'))->toBe('details')
        ->and($payload['values']['details'][0]['col1'])->toBe(-25)
        ->and($payload['errors'])->toBe([
            ['path' => ['details', '0', 'col1'], 'messages' => ['Quantity must be positive.']],
        ])
        ->and($payload['globalErrors'])->toBe([]);
});

it('round-trips link cells through request values, storage, and editor values', function () {
    $field = new Table([
        'handle' => 'links',
        'columns' => ['col1' => [
            'heading' => 'Link', 'handle' => 'link', 'type' => 'link',
            'types' => ['url', 'email'], 'showLabelField' => true, 'advancedFields' => ['urlSuffix'],
        ]],
    ]);

    $normalized = $field->normalizeValueFromRequest([
        ['col1' => ['type' => 'url', 'value' => ' https://example.com ', 'label' => 'Docs', 'urlSuffix' => 'page=2', 'target' => '_blank']],
        ['col1' => ['type' => 'url', 'value' => '']],
    ], null);
    $stored = $field->serializeValueForDb($normalized, new Entry);
    $payload = app(UiResolver::class)->resolve(Ui::make([
        UiField::make('Links', $field->uiControl(new FieldContext('links', value: $field->normalizeValue($stored, null)))),
    ]), new UiContext);
    $cell = $payload->nodes[0]->control->uis[0]->nodes[0]->control;

    expect($normalized[0]['link'])->toBeInstanceOf(LinkData::class)
        ->and((string) $normalized[0]['link']->getLink())->toBe('<a href="https://example.com?page=2">Docs</a>')
        ->and($normalized[1]['link'])->toBeNull()
        ->and($stored)->toBe([
            ['col1' => ['value' => 'https://example.com', 'type' => 'url', 'label' => 'Docs', 'urlSuffix' => '?page=2']],
            ['col1' => null],
        ])
        ->and($payload->values['links'][0]['col1'])->toBe([...$stored[0]['col1'], 'defaultLabel' => 'example.com'])
        ->and($cell->component)->toBe('craft:link')
        ->and(array_column($cell->props['types'], 'id'))->toBe(['url', 'email'])
        ->and($cell->props['advancedFields'])->toBe(['urlSuffix'])
        ->and($field->getSearchKeywords($normalized, new Entry))->toContain('https://example.com?page=2');
});

it('keeps submitted dates and times in the site timezone through storage and the editor', function () {
    $field = new Table([
        'handle' => 'events',
        'columns' => [
            'col1' => ['heading' => 'Date', 'handle' => 'date', 'type' => 'date'],
            'col2' => ['heading' => 'Time', 'handle' => 'time', 'type' => 'time'],
        ],
        'defaultRowValues' => [['col1' => '2026-10-24', 'col2' => '17:00']],
    ]);

    $normalized = $field->normalizeValueFromRequest([['col1' => '2026-10-24', 'col2' => '17:00']], null);
    $stored = $field->serializeValueForDb($normalized, new Entry);
    $control = $field->uiControl(new FieldContext('events', value: $field->normalizeValue($stored, null)));
    $payload = app(UiResolver::class)->resolve(Ui::make([UiField::make('Events', $control)]), new UiContext);

    expect($payload->values['events'][0])->toMatchArray(['col1' => '2026-10-24', 'col2' => '17:00'])
        ->and($control->props()['defaultValues'])->toMatchArray(['col1' => '2026-10-24', 'col2' => '17:00']);
});

it('validates link cells against their column settings', function (array $value, ?string $expected) {
    $field = new Table([
        'handle' => 'links',
        'columns' => ['col1' => ['heading' => 'Resource', 'handle' => 'resource', 'type' => 'link', 'types' => ['url'], 'maxLength' => 20]],
    ]);
    $errors = [];

    $field->validateTableData($field->normalizeValue([['col1' => $value]], null), function (string $path, string $message) use (&$errors): void {
        $errors[$path][] = $message;
    }, 'links');

    expect($errors['links.0.col1'][0] ?? null)->toBe($expected);
})->with([
    'valid URL' => [['type' => 'url', 'value' => 'https://example.com'], null],
    'disallowed type' => [['type' => 'email', 'value' => 'author@example.com'], 'Resource no longer allows Email links.'],
    'too long' => [['type' => 'url', 'value' => 'https://example.com/long-path'], 'Resource should contain at most 20 characters.'],
]);

it('indexes cells that serialize to arrays as words by default', function () {
    $cell = new class extends TableCell
    {
        public function serializeValue(mixed $value, bool $forDb = false): array
        {
            return ['first', ['second', 3]];
        }
    };

    expect($cell->searchKeywords(null))->toBe('first second 3');
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

    public function uiControl(TableCellContext $context): Control
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
