<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table as DbTable;
use CraftCms\Cms\Element\Jobs\ReplaceRelations;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\TableCells\Entries as EntriesCell;
use CraftCms\Cms\Field\TableCells\TableCellContext;
use CraftCms\Cms\Field\TableCellTypes;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Models\User as UserModel;
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

function relationTableSource(array $rows): Entry
{
    return EntryModel::factory()
        ->withField('related', Table::class, [
            'translationMethod' => 'none',
            'columns' => ['col1' => ['heading' => 'Entries', 'handle' => 'entries', 'type' => 'entries']],
        ], value: $rows)
        ->createElementWithFields()
        ->element;
}

function storedRelations(Entry $source): array
{
    return DB::table(DbTable::RELATIONS)
        ->where('sourceId', $source->id)
        ->orderBy('targetId')
        ->get(['sourceSiteId', 'targetId'])
        ->map(fn (object $row) => [(int) $row->sourceSiteId, (int) $row->targetId])
        ->all();
}

it('stores relation cells as the table’s relations, per site, rather than as field references', function () {
    [$a, $b] = EntryModel::factory(2)->create()->pluck('id')->all();
    $source = relationTableSource([['col1' => [$b, $a]], ['col1' => [$a]]]);

    expect(storedRelations($source))->toBe([[$source->siteId, $a], [$source->siteId, $b]])
        ->and(DB::table(DbTable::FIELDREFERENCES)->where('sourceId', $source->id)->count())->toBe(0);

    $source->setFieldValue('related', [['col1' => [$b]]]);
    Elements::saveElement($source);

    expect(storedRelations($source))->toBe([[$source->siteId, $b]]);
});

it('finds elements related through table cells with relatedTo, scoped to the table field', function () {
    [$target, $other] = EntryModel::factory(2)->create()->pluck('id')->all();
    $source = relationTableSource([['col1' => [$target]]]);

    $sources = fn (array $criteria) => Entry::find()->relatedTo($criteria)->ids();

    expect($sources(['targetElement' => $target]))->toBe([$source->id])
        ->and($sources(['targetElement' => $target, 'field' => 'related']))->toBe([$source->id])
        ->and($sources(['targetElement' => $other]))->toBe([])
        ->and(Entry::find()->relatedTo(['sourceElement' => $source->id, 'field' => 'related'])->ids())->toBe([$target]);
});

it('replaces related elements in table cells when relations are replaced', function (bool $oldTargetDeleted) {
    [$oldTarget, $newTarget, $other] = EntryModel::factory(3)->create()->pluck('id')->all();
    $source = relationTableSource([['col1' => [$oldTarget, $other]], ['col1' => [$other]]]);

    // The queued job usually runs after the old target is deleted
    if ($oldTargetDeleted) {
        expect(Elements::deleteElementById($oldTarget))->toBeTrue();
    }

    dispatch_sync(new ReplaceRelations(
        sourceElementType: Entry::class,
        targetElementType: Entry::class,
        sourceIds: [$source->id],
        oldTargetIds: [$oldTarget],
        newTargetId: $newTarget,
    ));

    $stored = Entry::find()->id($source->id)->one()->getFieldValue('related');

    expect($stored[0]['entries']->ids())->toBe([$newTarget, $other])
        ->and($stored[1]['entries']->ids())->toBe([$other])
        ->and(array_column(storedRelations($source), 1))->toBe([$newTarget, $other]);
})->with(['existing old target' => false, 'deleted old target' => true]);

it('points table cells at the prevailing element when elements are merged', function () {
    $merged = EntryModel::factory()->createElement();
    $prevailing = EntryModel::factory()->createElement();
    $other = EntryModel::factory()->createElement();
    $source = relationTableSource([['col1' => [$merged->id, $other->id]], ['col1' => [$merged->id, $prevailing->id]]]);

    expect(app(ElementDeletions::class)->mergeElements($merged, $prevailing))->toBeTrue();

    $stored = Entry::find()->id($source->id)->one()->getFieldValue('related');

    expect($stored[0]['entries']->ids())->toBe([$prevailing->id, $other->id])
        ->and($stored[1]['entries']->ids())->toBe([$prevailing->id])
        ->and(array_column(storedRelations($source), 1))->toBe([$prevailing->id, $other->id]);
});

/**
 * Returns an entry in the primary site and a second site, with its title translated in the second.
 *
 * @return array{Site, Entry}
 */
function translatedRelationTarget(): array
{
    $site = Site::factory()->create();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($site)->create();
    $target = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Hello']);
    expect(Elements::saveElement($target))->toBeTrue();

    $translated = Entry::find()->id($target->id)->siteId($site->id)->status(null)->one();
    $translated->title = 'Bonjour';
    expect(Elements::saveElement($translated))->toBeTrue();

    return [$site, $target];
}

it('queries propagated relation cells in the receiving element’s site, without changing the source value', function () {
    [$site, $target] = translatedRelationTarget();
    $source = EntryModel::factory()->createElement();
    $owner = clone $source;
    $owner->siteId = $site->id;
    $field = relationTable();

    $original = $field->normalizeValue([['col1' => [$target->id]]], $source);
    $propagated = $field->normalizeValue($original, $owner);

    expect($propagated[0]['entries']->siteId)->toBe($site->id)
        ->and($propagated[0]['col1'])->toBe($propagated[0]['entries'])
        ->and($propagated[0]['entries']->one()->title)->toBe('Bonjour')
        ->and($original[0]['entries']->siteId)->toBe($source->siteId)
        ->and($original[0]['entries']->one()->title)->toBe('Hello');
});

it('indexes relation cells with the related elements’ titles in the element’s site', function () {
    [$site, $target] = translatedRelationTarget();
    $owner = EntryModel::factory()->createElement();
    $owner->siteId = $site->id;
    $field = relationTable();

    $value = $field->normalizeValue([['col1' => [$target->id]]], $owner);

    expect($field->cellType($field->columns['col1'])->searchKeywords($value[0]['entries']))->toBe('Bonjour');
});

it('caps related elements in GraphQL at maxGraphqlResults', function () {
    $users = array_map(fn () => UserModel::factory()->createElement(), range(1, 3));
    $field = new Table([
        'handle' => 'related',
        'columns' => ['col1' => ['heading' => 'Users', 'handle' => 'users', 'type' => 'users']],
    ]);
    app(Gql::class)->setActiveSchema(new GqlSchema(['scope' => ['usergroups.everyone:read']]));
    Cms::config()->maxGraphqlResults = 2;

    $value = $field->normalizeValue([['col1' => array_map(fn ($user) => $user->id, $users)]], null);

    expect(array_map(fn ($user) => $user->id, $field->cellType($field->columns['col1'])->gqlValue($value[0]['users'])))
        ->toBe([$users[0]->id, $users[1]->id]);
});
