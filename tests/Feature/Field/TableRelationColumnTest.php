<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table as DbTable;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\TableCells\Entries as EntriesCell;
use CraftCms\Cms\Field\TableCells\TableCellContext;
use CraftCms\Cms\Field\TableCellTypes;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Support\Facades\DB;

function relationTable(array $column = []): Table
{
    return new Table([
        'handle' => 'related',
        'columns' => ['col1' => ['heading' => 'Entries', 'handle' => 'entries', 'type' => 'entries', ...$column]],
    ]);
}

it('normalizes entries cells to queries for the related entries in their order', function () {
    $targets = EntryModel::factory(2)->create()->pluck('id')->all();
    $field = relationTable();

    $rows = $field->normalizeValue([['col1' => [$targets[1], 999999, $targets[0]]], ['col1' => null]], null);

    expect($rows[0]['entries'])->toBeInstanceOf(ElementQueryInterface::class)
        ->and($rows[0]['entries']->ids())->toBe([$targets[1], $targets[0]])
        ->and($rows[1]['entries']->ids())->toBe([])
        ->and($field->serializeValueForDb($rows, new Entry))->toBe([['col1' => [$targets[1], $targets[0]]], ['col1' => []]]);
});

it('validates the number of related elements in each cell', function () {
    $targets = EntryModel::factory(3)->create()->pluck('id')->all();
    $field = relationTable(['minRelations' => 2, 'maxRelations' => 2]);
    $errors = [];

    $field->validateTableData(
        $field->normalizeValue([['col1' => [$targets[0]]], ['col1' => [$targets[0], $targets[1]]], ['col1' => $targets]], null),
        function (string $error) use (&$errors): void {
            $errors[] = $error;
        },
    );

    expect($errors)->toBe([
        'Entries should contain at least 2 selections.',
        'Entries should contain at most 2 selections.',
    ]);
});

it('applies relation settings stored on the column', function () {
    $cell = app(TableCellTypes::class)->create([
        'heading' => 'Entries',
        'handle' => 'entries',
        'type' => 'entries',
        'sources' => ['section:abc'],
        'maxRelations' => '3',
        'minRelations' => '',
        'selectionLabel' => 'Add an entry',
    ]);

    expect($cell)->toBeInstanceOf(EntriesCell::class)
        ->and($cell->getSettings())->toMatchArray([
            'sources' => ['section:abc'],
            'maxRelations' => 3,
            'minRelations' => null,
            'selectionLabel' => 'Add an entry',
        ]);
});

it('keeps the edited element out of its own entries cells', function () {
    $source = EntryModel::factory()->createElement();
    $field = relationTable();

    $payload = app(UiResolver::class)->resolve(Ui::make([
        UiField::make('Related', $field->uiControl(new FieldContext('related', value: $field->normalizeValue([['col1' => []]], $source), element: $source))),
    ]), new UiContext);

    expect($payload->nodes[0]->control->uis[0]->nodes[0]->control->props['disabledElementIds'] ?? [])->toBe([$source->id]);
});

it('edits entries cells with the element selector, scoped to the field', function () {
    $target = EntryModel::factory()->createElement();
    $field = relationTable(['selectionLabel' => 'Pick']);
    $value = $field->normalizeValue([['col1' => [$target->id]]], null);

    $control = $field->uiControl(new FieldContext('related', value: $value));
    $cellControl = $field->cellType($field->columns['col1'])->uiControl(new TableCellContext(['col1'], $value[0]['col1']));

    expect($control->getValueScope())->toBe(Table::class)
        ->and($cellControl->getValue())->toBe([$target->id])
        ->and($cellControl->props()['selectionLabel'])->toBe('Pick')
        ->and($cellControl->props()['elementType'])->toBe(Entry::class);
});

it('exposes related entries on entries cell GraphQL types', function () {
    $columns = relationTable()->columns;

    expect((string) TableRow::prepareRowFieldDefinition($columns)['entries'])->toBe('[EntryInterface]!')
        ->and((string) TableRow::prepareRowFieldDefinition($columns, input: true)['entries'])->toBe('[Int]');
});

it('tracks and replaces relations in table cells', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $other = EntryModel::factory()->createElement();
    $result = EntryModel::factory()
        ->withField('related', Table::class, [
            'columns' => ['col1' => ['heading' => 'Entries', 'handle' => 'entries', 'type' => 'entries']],
        ], value: [
            ['col1' => [$oldTarget->id, $other->id]],
            ['col1' => [$other->id]],
        ])
        ->createElementWithFields();
    $source = $result->element;
    /** @var Table $field */
    $field = $source->getFieldLayout()->getFieldByHandle('related');
    $references = fn () => DB::table(DbTable::FIELDREFERENCES)->where('sourceId', $source->id)->orderBy('targetId')->pluck('targetId')->all();

    expect($references())->toBe([$oldTarget->id, $other->id])
        ->and($field->replaceReferences($source, [$newTarget->id], $oldTarget->id))->toBeFalse()
        ->and($field->replaceReferences($source, [$oldTarget->id], $newTarget->id))->toBeTrue();

    Elements::saveElement($source);
    $stored = Entry::find()->id($source->id)->one()->getFieldValue('related');

    expect($stored[0]['entries']->ids())->toBe([$newTarget->id, $other->id])
        ->and($stored[1]['entries']->ids())->toBe([$other->id])
        ->and($references())->toBe([$newTarget->id, $other->id]);
});
