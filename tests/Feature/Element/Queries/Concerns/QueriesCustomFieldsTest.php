<?php

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
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
