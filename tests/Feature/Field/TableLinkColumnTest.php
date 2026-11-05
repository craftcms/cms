<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table as DbTable;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Gql\Types\Generators\TableRowType;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    new ReflectionProperty(BaseElementLinkType::class, 'fetchedElements')->setValue(null, []);
});

function linkTable(array $column = []): Table
{
    return new Table([
        'handle' => 'links',
        'translationMethod' => 'site',
        'columns' => ['col1' => ['heading' => 'Link', 'handle' => 'link', 'type' => 'link', ...$column]],
    ]);
}

it('keeps link cells with unavailable link types unchanged when the table is edited', function () {
    $field = linkTable();
    $raw = ['type' => 'missing', 'value' => 'opaque', 'label' => 'Kept'];
    $normalized = $field->normalizeValueFromRequest([['col1' => $raw]], null);
    $errors = [];
    $field->validateTableData($normalized, function (string $error) use (&$errors): void {
        $errors[] = $error;
    });
    $payload = app(UiResolver::class)->resolve(Ui::make([
        UiField::make('Links', $field->uiControl(new FieldContext('links', value: $normalized))),
    ]), new UiContext);
    $cell = $payload->nodes[0]->control->uis[0]->nodes[0]->control;

    expect($normalized[0]['link'])->toBe($raw)
        ->and($field->serializeValueForDb($normalized, new Entry))->toBe([['col1' => $raw]])
        ->and($errors)->toBe([])
        ->and($cell->mode)->toBe(ControlMode::Editable)
        ->and($cell->props['registeredTypes'])->toContain('url')->not->toContain('missing');
});

it('resolves link cells with unavailable link types to empty links in GraphQL', function () {
    $field = linkTable(['types' => ['entry', 'url']]);
    $row = $field->normalizeValue([['col1' => ['type' => 'missing', 'value' => 'opaque']]], null)[0];
    $schema = new Schema(['query' => new ObjectType([
        'name' => 'Query',
        'fields' => ['row' => ['type' => TableRowType::generateType($field), 'resolve' => fn () => $row]],
    ])]);

    $result = GraphQL::executeQuery($schema, '{ row { link { type url entry { id } element { id } } } }')->toArray();

    expect($result)->toBe(['data' => ['row' => ['link' => [
        'type' => null, 'url' => null, 'entry' => null, 'element' => null,
    ]]]]);
});

it('exposes linked elements on link cell GraphQL types', function () {
    $columns = linkTable()->columns;

    $type = TableRow::prepareRowFieldDefinition($columns)['link'];

    expect($type->name)->toBe('LinkData')
        ->and($type->getFields())->toHaveKeys(['element', 'entry'])
        ->and(TableRow::prepareRowFieldDefinition($columns, input: true)['link']->name)->toBe('LinkDataInput');
});

it('localizes element links when table values propagate to another site', function (bool $propagating) {
    $site = Site::factory()->create();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($site)->create();
    $target = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();
    expect(Elements::saveElement($target))->toBeTrue();
    $source = EntryModel::factory()->createElement();
    $owner = clone $source;
    $owner->siteId = $site->id;
    $owner->propagateAll = true;
    $owner->propagating = $propagating;
    if ($propagating) {
        $owner->propagatingFrom = $source;
    } else {
        $owner->duplicateOf = $source;
    }

    $field = linkTable(['types' => ['entry'], 'showLabelField' => true]);
    $original = $field->normalizeValue([['col1' => ['type' => 'entry', 'value' => "{entry:$target->id@$target->siteId:url}", 'label' => 'Kept']]], null);
    $localized = $field->normalizeValue($original, $owner);

    expect($localized[0]['link']->serialize())->toBe(['value' => "{entry:$target->id@$site->id:url}", 'type' => 'entry', 'label' => 'Kept'])
        ->and($localized[0]['col1'])->toBe($localized[0]['link'])
        ->and($original[0]['link']->serialize()['value'])->toBe("{entry:$target->id@$target->siteId:url}");
})->with([true, false]);

it('localizes a table value’s element links with a fixed number of queries', function () {
    $site = Site::factory()->create();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($site)->create();
    $targets = collect(range(1, 3))->map(function () use ($section, $entryType) {
        $target = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();
        Elements::saveElement($target);

        return $target;
    });
    $owner = EntryModel::factory()->createElement();
    $owner->propagating = true;
    $owner->propagateAll = true;
    $owner->propagatingFrom = clone $owner;
    $owner->siteId = $site->id;
    $field = linkTable(['types' => ['entry']]);

    $queryCount = function (int $links) use ($field, $owner, $targets): int {
        new ReflectionProperty(BaseElementLinkType::class, 'fetchedElements')->setValue(null, []);
        $rows = $targets->take($links)->map(fn ($target) => ['col1' => ['type' => 'entry', 'value' => "{entry:$target->id@$target->siteId:url}"]])->all();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $field->normalizeValue($rows, $owner);
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    expect($queryCount(3))->toBe($queryCount(1));
});

it('tracks and replaces element links in table cells', function () {
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();
    $result = EntryModel::factory()
        ->withField('links', Table::class, [
            'columns' => ['col1' => ['heading' => 'Link', 'handle' => 'link', 'type' => 'link', 'types' => ['entry', 'url'], 'showLabelField' => true]],
        ], value: [
            ['col1' => ['type' => 'entry', 'value' => "{entry:$oldTarget->id@$oldTarget->siteId:url}", 'label' => 'Old']],
            ['col1' => ['type' => 'entry', 'value' => "{entry:$oldTarget->id@$oldTarget->siteId:url}"]],
            ['col1' => ['type' => 'url', 'value' => 'https://example.com']],
        ])
        ->createElementWithFields();
    $source = $result->element;
    /** @var Table $field */
    $field = $source->getFieldLayout()->getFieldByHandle('links');

    expect(DB::table(DbTable::FIELDREFERENCES)->where('sourceId', $source->id)->pluck('targetId')->all())->toBe([$oldTarget->id])
        ->and($field->replaceReferences($source, [$newTarget->id], $oldTarget->id))->toBeFalse()
        ->and($field->replaceReferences($source, [$oldTarget->id], $newTarget->id))->toBeTrue();

    Elements::saveElement($source);
    $stored = Entry::find()->id($source->id)->one()->getFieldValue('links');

    expect($stored[0]['link']->serialize())->toBe(['value' => "{entry:$newTarget->id@$oldTarget->siteId:url}", 'type' => 'entry', 'label' => 'Old'])
        ->and($stored[1]['link']->getElement()->id)->toBe($newTarget->id)
        ->and((string) $stored[2]['link'])->toBe('https://example.com')
        ->and(DB::table(DbTable::FIELDREFERENCES)->where('sourceId', $source->id)->pluck('targetId')->all())->toBe([$newTarget->id]);
});

it('loads the elements of all element links in a table value together', function () {
    $targets = EntryModel::factory(3)->create()->pluck('id')->all();
    $field = linkTable(['types' => ['entry']]);
    $rows = $field->normalizeValue(array_map(fn (int $id) => ['col1' => ['type' => 'entry', 'value' => "{entry:$id@1:url}"]], $targets), null);

    expect($rows[0]['link']->getElement()->id)->toBe($targets[0]);

    DB::enableQueryLog();
    $ids = [$rows[1]['link']->getElement()->id, $rows[2]['link']->getElement()->id];

    expect($ids)->toBe([$targets[1], $targets[2]])
        ->and(DB::getQueryLog())->toBe([]);
});
