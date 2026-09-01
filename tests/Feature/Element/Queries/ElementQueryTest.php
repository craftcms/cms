<?php

use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Tpetry\QueryExpressions\Language\Alias;

it('can run basic queries', function () {
    expect(entryQuery()->all())->toBeEmpty();

    $elements = EntryModel::factory(5)->create();

    expect(entryQuery()->all())->toHaveCount(5);
    expect(entryQuery()->get())->toHaveCount(5);
    expect(entryQuery()->one())->toBeInstanceOf(Entry::class);
    expect(entryQuery()->first())->toBeInstanceOf(Entry::class);
    expect(entryQuery()->firstOrFail())->toBeInstanceOf(Entry::class);
    expect(entryQuery()->limit(3)->get())->toHaveCount(3);
    expect(entryQuery()->find($elements[0]->id))->toBeInstanceOf(Entry::class);
    expect(entryQuery()->where('elements.id', $elements[0]->id))->sole()->toBeInstanceOf(Entry::class);
    expect(entryQuery()->offset(4)->limit(10)->get())->toHaveCount(1);
    expect(entryQuery()->limit(1)->get())->toHaveCount(1);
    expect(entryQuery()->offset(4)->get())->toHaveCount(1);

    $this->expectException(MultipleRecordsFoundException::class);
    entryQuery()->sole();

    $this->expectException(ModelNotFoundException::class);
    entryQuery()->findOrFail(999);
});

it('can create with an array of parameters', function () {
    EntryModel::factory()->create();
    $entry = EntryModel::factory()->create();

    expect(entryQuery(['id' => $entry->id])->count())->toBe(1);
});

test('trashed', function () {
    EntryModel::factory(2)->create();
    EntryModel::factory(2)->trashed()->create();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->trashed(true)->count())->toBe(2);
    expect(entryQuery()->trashed(null)->count())->toBe(4);
});

it('ignores inner query pagination when getting a pagination count', function () {
    EntryModel::factory(5)->create();

    $query = entryQuery()->offset(2)->limit(2);

    expect($query->getCountForPagination())->toBe(5);
    expect($query->get())->toHaveCount(2);
});

it('sources concrete element queries from their element tables first', function (callable $queryFactory, string $sourceTable) {
    /** @var Builder $builder */
    $builder = $queryFactory();

    expect($builder->from)->toBe($sourceTable);

    $joins = collect($builder->joins);

    expect($joins)->toHaveCount(2);
    expect(normalizeJoinAlias($joins[0]))->toBe('elements as elements');
    expect($joins[0]->wheres[0])->toMatchArray([
        'type' => 'Column',
        'first' => 'elements.id',
        'operator' => '=',
        'second' => "$sourceTable.id",
        'boolean' => 'and',
    ]);

    expect(normalizeJoinAlias($joins[1]))->toBe('elements_sites as elements_sites');
    expect($joins[1]->wheres[0])->toMatchArray([
        'type' => 'Column',
        'first' => 'elements_sites.elementId',
        'operator' => '=',
        'second' => 'elements.id',
        'boolean' => 'and',
    ]);
})->with([
    'entries' => [fn () => entryQuery()->getQuery(), 'entries'],
    'users' => [fn () => userQuery()->getQuery(), 'users'],
    'assets' => [fn () => assetQuery()->getQuery(), 'assets'],
    'addresses' => [fn () => new AddressQuery()->getQuery(), 'addresses'],
    'contentblocks' => [fn () => new ContentBlockQuery()->getQuery(), 'contentblocks'],
]);

it('does not duplicate pending before query callbacks when cloned while preparing', function () {
    $query = new ElementQuery;
    $clone = null;

    $query->beforeQuery(function (ElementQuery $query) use (&$clone) {
        $clone ??= clone $query;
    });

    $query->beforeQuery(function (ElementQuery $query) {
        $query->getQuery()->leftJoin(new Alias('search_marker', 'search_marker'), 'search_marker.id', '=', 'elements.id');
    });

    $query->applyBeforeQueryCallbacks();

    expect($clone)->toBeInstanceOf(ElementQuery::class);

    $clone->applyBeforeQueryCallbacks();

    $markerJoins = collect($clone->getQuery()->joins)
        ->filter(fn (JoinClause $join) => normalizeJoinAlias($join) === 'search_marker as search_marker');

    expect($markerJoins)->toHaveCount(1);
});

function normalizeJoinAlias(JoinClause $join): string
{
    $table = $join->table;

    expect($table)->toBeInstanceOf(Alias::class);

    return preg_replace('/[`"\[\]]/', '', $table->getValue($join->getGrammar()));
}
