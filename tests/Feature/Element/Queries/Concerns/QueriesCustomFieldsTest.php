<?php

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

class TestActiveQueryField extends PlainText
{
    public static ?ElementQuery $activeQueryDuringModify = null;

    public static bool $throw = false;

    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        self::$activeQueryDuringModify = ElementQuery::$activeQuery;

        if (self::$throw) {
            throw new RuntimeException('Active query failure.');
        }

        return parent::modifyQuery($query, $instances, $value);
    }

    public static function reset(): void
    {
        self::$activeQueryDuringModify = null;
        self::$throw = false;
    }
}

beforeEach(function () {
    TestActiveQueryField::reset();
    ElementQuery::$activeQuery = null;
});

afterEach(function () {
    TestActiveQueryField::reset();
    ElementQuery::$activeQuery = null;
});

it('can query custom fields', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'textField',
        'type' => PlainText::class,
    ]);

    EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field))
        ->create();

    /** @var Entry $entry */
    $entry = entryQuery()->first();
    $entry->title = 'Test entry';
    $entry->setFieldValue('textField', 'Foo');

    Elements::saveElement($entry);

    expect(entryQuery()->textField('Foo')->count())->toBe(1);
    expect(entryQuery()->textField('Fo*')->count())->toBe(1);
    expect(entryQuery()->textField([
        'value' => 'fo*',
        'caseInsensitive' => true,
    ])->count())->toBe(1);

    // SQLite's LIKE operator is case-insensitive for ASCII by default and does not
    // support case-sensitive wildcard matching without custom functions or GLOB.
    if (DB::getDriverName() !== 'sqlite') {
        expect(entryQuery()->textField([
            'value' => 'fo*',
            'caseInsensitive' => false,
        ])->count())->toBe(0);
    }

    expect(entryQuery()->textField('bar')->count())->toBe(0);
});

it('only stores explicitly supplied custom field criteria', function () {
    $queriedField = Field::factory()->create([
        'handle' => 'queriedField',
        'type' => PlainText::class,
    ]);
    Field::factory()->create([
        'handle' => 'unusedField',
        'type' => PlainText::class,
    ]);

    EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($queriedField))
        ->create();

    $query = entryQuery()->queriedField('Foo');
    $queryWithExplicitNull = entryQuery()
        ->queriedField('Foo')
        ->unusedField(null);
    $querySql = $query->toSql();
    $queryBindings = $query->getBindings();
    $clone = clone $query;
    $roundTrippedCriteria = $query->getCriteria()
        |> (fn (array $criteria) => array_intersect_key($criteria, array_flip(['queriedField', 'unusedField'])))
        |> serialize(...)
        |> unserialize(...);

    expect($query->getCriteria())
        ->toHaveKey('queriedField', 'Foo')
        ->not->toHaveKey('unusedField')
        ->and($queryWithExplicitNull->getCriteria())
        ->toHaveKey('unusedField', null)
        ->and($clone->getCriteria())
        ->toHaveKey('queriedField', 'Foo')
        ->not->toHaveKey('unusedField')
        ->and($roundTrippedCriteria)
        ->toHaveKey('queriedField', 'Foo')
        ->not->toHaveKey('unusedField')
        ->and(entryQuery()->unusedField('Foo')->count())
        ->toBe(0)
        ->and($querySql)
        ->toBe($queryWithExplicitNull->toSql())
        ->and($queryBindings)
        ->toEqual($queryWithExplicitNull->getBindings());
});

it('includes entries whose multi-option field is still empty in not one of queries', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'checkboxField',
        'type' => Checkboxes::class,
        'settings' => [
            'options' => [
                ['label' => 'Option A', 'value' => 'a'],
                ['label' => 'Option B', 'value' => 'b'],
            ],
        ],
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $neverModifiedEntry = EntryModel::factory()
        ->withFieldLayout($fieldLayout)
        ->createElement(['title' => 'Never modified']);

    $clearedEntry = EntryModel::factory()
        ->withFieldLayout($fieldLayout)
        ->createElement(['title' => 'Cleared']);
    $clearedEntry->setFieldValue('checkboxField', []);
    Elements::saveElement($clearedEntry);

    $otherOptionEntry = EntryModel::factory()
        ->withFieldLayout($fieldLayout)
        ->createElement(['title' => 'Other option']);
    $otherOptionEntry->setFieldValue('checkboxField', ['b']);
    Elements::saveElement($otherOptionEntry);

    $excludedEntry = EntryModel::factory()
        ->withFieldLayout($fieldLayout)
        ->createElement(['title' => 'Excluded option']);
    $excludedEntry->setFieldValue('checkboxField', ['a']);
    Elements::saveElement($excludedEntry);

    $resultIds = entryQuery()
        ->checkboxField(['not', 'a'])
        ->ids();

    expect($resultIds)
        ->toContain($neverModifiedEntry->id, $clearedEntry->id, $otherOptionEntry->id)
        ->not->toContain($excludedEntry->id);
});

it('exposes the active query while applying custom field query params', function () {
    $field = Field::factory()->create([
        'handle' => 'activeQueryField',
        'type' => TestActiveQueryField::class,
    ]);

    EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field))
        ->create();

    $query = entryQuery()->activeQueryField('Foo');

    expect(ElementQuery::$activeQuery)->toBeNull();

    $query->count();

    expect(TestActiveQueryField::$activeQueryDuringModify)->toBe($query)
        ->and(ElementQuery::$activeQuery)->toBeNull();
});

it('clears the active query when custom field query modification fails', function () {
    $field = Field::factory()->create([
        'handle' => 'activeQueryField',
        'type' => TestActiveQueryField::class,
    ]);

    EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field))
        ->create();

    TestActiveQueryField::$throw = true;

    $query = entryQuery()->activeQueryField('Foo');

    expect(fn () => $query->count())->toThrow(RuntimeException::class, 'Active query failure.');

    expect(TestActiveQueryField::$activeQueryDuringModify)->toBe($query)
        ->and(ElementQuery::$activeQuery)->toBeNull();
});
