<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Models\Address;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

it('preserves select expressions bindings and mapped aliases across element types', function (Closure $factory) {
    $element = $factory();
    $query = $element::find()->id($element->id)
        ->select(['id as selectedId', new Alias(new Expression('6 * 7'), 'computedValue')])
        ->selectRaw('? + 22 as bound_value', [20])
        ->selectRaw("'text as value' as text_value")
        ->asArray();

    $expected = [
        'selectedId' => $element->id,
        'computedValue' => 42,
        'bound_value' => 42,
        'text_value' => 'text as value',
    ];

    expect($query->one())->toMatchArray($expected)
        ->and($query->one())->toMatchArray($expected);
})->with([
    'entries' => fn () => EntryModel::factory()->createElement(),
    'assets' => fn () => Asset::factory()->createElement(),
    'users' => fn () => User::factory()->createElement(),
    'addresses' => fn () => Address::factory()->createElement(),
    'content blocks' => function () {
        $field = Field::factory()->create(['type' => ContentBlockField::class]);
        $owner = EntryModel::factory()->create();
        $element = ElementModel::factory()->create(['type' => ContentBlock::class]);

        DB::table(Table::CONTENTBLOCKS)->insert([
            'id' => $element->id,
            'primaryOwnerId' => $owner->id,
            'fieldId' => $field->id,
        ]);

        return ContentBlock::find()->id($element->id)->one();
    },
]);

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

it('keeps its own conditions when the query has a top-level orWhere', function () {
    $live = EntryModel::factory()->create();
    $disabled = EntryModel::factory()->for($live->section)->create();
    $trashed = EntryModel::factory()->for($live->section)->trashed()->create();
    $otherSection = EntryModel::factory()->create();
    ElementModel::whereKey($disabled->id)->update(['enabled' => false]);

    $ids = entryQuery()
        ->sectionId($live->sectionId)
        ->where('elements.id', $disabled->id)
        ->orWhere('elements.id', $trashed->id)
        ->orWhere('elements.id', $otherSection->id)
        ->orWhere('elements.id', $live->id)
        ->ids();

    expect($ids)->toBe([$live->id]);
});

it('compares bound dates in UTC', function () {
    expect(now()->getOffset())->not->toBe(0);

    $past = EntryModel::factory()->create(['postDate' => Query::prepareDateForDb(now()->subHour())]);
    $future = EntryModel::factory()->create(['postDate' => Query::prepareDateForDb(now()->addHour())]);

    expect(entryQuery()->status(null)->where('entries.postDate', '<=', now())->ids())->toBe([$past->id])
        ->and(entryQuery()->status(null)->whereFuture('entries.postDate')->ids())->toBe([$future->id])
        ->and(entryQuery()->status(null)->whereBetween('entries.postDate', [now()->subMinutes(90), now()])->ids())->toBe([$past->id]);
});

it('reads value() from the first element', function () {
    $result = EntryModel::factory()->withField('teaser', PlainText::class, value: 'Teaser B')->createElementWithFields();
    $b = $result->element;
    $a = EntryModel::factory()->state(['typeId' => $b->typeId, 'sectionId' => $b->sectionId])->createElement();
    $a->title = 'A';
    $a->setFieldValue('teaser', 'Teaser A');
    $b->title = 'B';
    Elements::saveElement($a);
    Elements::saveElement($b);

    $query = fn () => entryQuery()->sectionId($b->sectionId)->orderBy('title');

    expect($query()->value('title'))->toBe('A')
        ->and($query()->value('elements.id'))->toBe($a->id)
        ->and($query()->value('teaser'))->toBe('Teaser A')
        ->and($query()->value('postDate'))->toBeInstanceOf(DateTimeInterface::class)
        ->and($query()->id($b->id)->soleValue('teaser'))->toBe('Teaser B')
        ->and($query()->id(-1)->value('title'))->toBeNull()
        ->and(fn () => $query()->id(-1)->valueOrFail('title'))->toThrow(ElementNotFoundException::class)
        ->and($query()->select(['title', 'teaser'])->asArray()->get()->map(fn (array $row) => [$row['title'], $row['teaser']])->all())
        ->toBe([['A', 'Teaser A'], ['B', 'Teaser B']]);
});

it('plucks and aggregates mapped columns and custom fields', function () {
    $result = EntryModel::factory()->withField('num', Number::class, value: 5)->createElementWithFields();
    $first = $result->element;
    $second = EntryModel::factory()->withFieldLayout(FieldLayout::factory()->forField($result->fields->get('num')))->createElement();
    $second->setFieldValue('num', 20);
    Elements::saveElement($second);
    $first->title = 'First';
    $second->title = 'Second';
    Elements::saveElement($first);
    Elements::saveElement($second);

    $query = fn () => entryQuery()->id([$first->id, $second->id])->orderBy('title');

    expect($query()->pluck('num')->map(fn ($value) => (int) $value)->all())->toBe([5, 20])
        ->and($query()->pluck('title', 'id')->all())->toBe([$first->id => 'First', $second->id => 'Second'])
        ->and($query()->implode('title', ', '))->toBe('First, Second')
        ->and((int) $query()->sum('num'))->toBe(25)
        ->and((int) $query()->max('num'))->toBe(20)
        ->and((float) $query()->avg('num'))->toBe(12.5)
        ->and($query()->count('num'))->toBe(2)
        ->and($query()->max('dateCreated'))->not->toBeNull()
        ->and($query()->addSelect(['secondId' => entryQuery()->id($second->id)->select('elements.id')->limit(1)])->pluck('secondId')->all())->toBe([$second->id, $second->id])
        ->and($query()->selectRaw('? + 1 as bound', [6])->pluck('bound')->map(fn ($value) => (int) $value)->all())->toBe([7, 7])
        ->and($query()->addSelect('title as heading')->pluck('heading')->all())->toBe(['First', 'Second'])
        ->and(entryQuery()->id([$first->id, $second->id])->addSelect('title as heading')->orderByDesc('heading')->ids())->toBe([$second->id, $first->id])
        ->and(entryQuery()->id([$first->id, $second->id])->addSelect('title as heading')->orderByDesc('heading')->pluck('title')->all())->toBe(['Second', 'First'])
        ->and(entryQuery()->revisions()->status(null)->orderByDesc('revisionNum')->ids())
        ->toBe(collect(entryQuery()->revisions()->status(null)->orderByDesc('revisionNum')->all())->pluck('id')->all());
});

it('uses element queries as subqueries', function () {
    [$a, $b, $c] = EntryModel::factory(3)->create()->pluck('id')->all();
    $subquery = fn () => entryQuery()->id([$a, $b])->select('elements.id');
    $query = fn () => entryQuery()->id([$a, $b, $c])->orderBy('elements.id');

    expect($query()->whereIn('elements.id', $subquery())->ids())->toBe([$a, $b])
        ->and($query()->whereNotIn('elements.id', $subquery())->ids())->toBe([$c])
        ->and($query()->joinSub($subquery(), 'sub', 'sub.id', '=', 'elements.id')->ids())->toBe([$a, $b])
        ->and($query()->whereIn('elements.id', entryQuery()->section('nonexistent')->select('elements.id'))->ids())->toBe([])
        ->and(DB::table(Table::ELEMENTS)->whereIn('id', $subquery()->toBase())->orderBy('id')->pluck('id')->all())->toBe([$a, $b])
        ->and(entryQuery()->typeId([])->select('elements.id')->where('elements.id', $a)->orWhere('elements.id', $b)->toBase()->pluck('id')->all())->toBe([])
        ->and(entryQuery()->typeId([])->select('id')->toBase()->get()->all())->toBe([])
        ->and(array_keys($query()->select(['elements.id', 'title'])->asArray()->one()))->toBe(['id', 'title'])
        ->and($query()->select(['enabled'])->groupBy('enabled')->reorder('enabled')->asArray()->get())->toHaveCount(1);
});

it('cursor paginates elements by date columns', function () {
    expect(now()->getOffset())->not->toBe(0);

    $ids = collect(['2026-01-03', '2026-01-01', '2026-01-02'])
        ->map(fn (string $day) => EntryModel::factory()->create(['postDate' => "$day 23:30:00"])->id);
    $query = fn () => entryQuery()->id($ids->all())->orderBy('postDate')->orderBy('elements.id');

    $pages = [];
    $cursor = null;

    do {
        $page = $query()->cursorPaginate(1, cursor: $cursor);
        $pages[] = $page->first()->id;
        $cursor = $page->nextCursor();
    } while ($cursor !== null && count($pages) < 5);

    $aliasQuery = fn () => entryQuery()->id($ids->all())->select('elements.id as entryId')->asArray()->orderBy('entryId');
    $aliasPage = $aliasQuery()->cursorPaginate(1, cursor: $aliasQuery()->cursorPaginate(1)->nextCursor());
    $rawAliasQuery = fn () => entryQuery()->id($ids->all())->selectRaw(DB::getQueryGrammar()->wrap('elements.id').' as entry_key')->asArray()->orderBy('entry_key');
    $rawAliasPage = $rawAliasQuery()->cursorPaginate(1, cursor: $rawAliasQuery()->cursorPaginate(1)->nextCursor());
    $unionQuery = fn () => entryQuery()->id($ids[0])->union(entryQuery()->id([$ids[1], $ids[2]]))->orderByDesc('id');
    $unionPage = $unionQuery()->cursorPaginate(2);

    expect($page->first())->toBeInstanceOf(Entry::class)
        ->and($pages)->toBe([$ids[1], $ids[2], $ids[0]])
        ->and($query()->cursorPaginate(1, cursor: $page->previousCursor())->pluck('id')->all())->toBe([$ids[2]])
        ->and(collect($aliasPage->items())->map(fn (array $row) => (int) $row['entryId'])->all())->toBe([$ids->sort()->values()[1]])
        ->and(collect($rawAliasPage->items())->map(fn (array $row) => (int) $row['entry_key'])->all())->toBe([$ids->sort()->values()[1]])
        ->and($unionQuery()->cursorPaginate(2, cursor: $unionPage->nextCursor())->pluck('id')->all())->toBe([$ids->sort()->values()[0]])
        ->and(fn () => entryQuery()->id($ids->all())->fixedOrder()->cursorPaginate(1))->toThrow(InvalidArgumentException::class)
        ->and(fn () => entryQuery()->id($ids->all())->orderByRaw('elements.id desc')->cursorPaginate(1))->toThrow(InvalidArgumentException::class);
});

it('resolves mapped columns and custom fields in where clauses and groupBy', function () {
    $result = EntryModel::factory()
        ->withField('teaser', PlainText::class, value: 'Alpha AI')
        ->withField('num', Number::class, value: 5)
        ->createElementWithFields();
    $alpha = $result->element;
    $layout = FieldLayout::factory()->forField($result->fields->get('teaser'))->forField($result->fields->get('num'));
    $entries = [$alpha];

    foreach ([['Beta', 20], ['Gamma AI', 10], [null, 10]] as [$teaser, $num]) {
        $entry = EntryModel::factory()->withFieldLayout($layout)->createElement();
        $entry->setFieldValue('teaser', $teaser);
        $entry->setFieldValue('num', $num);
        Elements::saveElement($entry);
        $entries[] = $entry;
    }

    [$alpha, $beta, $gamma, $empty] = array_column($entries, 'id');
    $query = fn () => entryQuery()->id([$alpha, $beta, $gamma, $empty])->orderBy('elements.id');

    expect($query()->where('id', $beta)->ids())->toBe([$beta])
        ->and($query()->whereIn('id', [$alpha, $gamma])->ids())->toBe([$alpha, $gamma])
        ->and($query()->where('teaser', 'Beta')->ids())->toBe([$beta])
        ->and($query()->whereLike('teaser', '%AI%')->ids())->toBe([$alpha, $gamma])
        ->and($query()->where('num', '>', 7)->ids())->toBe([$beta, $gamma, $empty])
        ->and($query()->whereAny(['title', 'teaser'], 'like', 'Gamma%')->ids())->toBe([$gamma])
        ->and($query()->whereNull('teaser')->ids())->toBe([$empty])
        ->and($query()->where(fn (Illuminate\Contracts\Database\Query\Builder $query) => $query->where('num', 5)->orWhere('teaser', 'Beta'))->ids())->toBe([$alpha, $beta])
        ->and($query()->whereColumn('dateCreated', '<=', 'dateUpdated')->count())->toBe(4)
        ->and($query()->groupBy('num')->reorder('num')->pluck('num')->map(fn ($num) => (int) $num)->all())->toBe([5, 10, 20]);
});

it('passes element queries to where() closures', function () {
    $result = EntryModel::factory()
        ->withField('role', PlainText::class, value: 'admin')
        ->withField('num', Number::class, value: 1)
        ->createElementWithFields();
    $admin = $result->element;
    $layout = FieldLayout::factory()->forField($result->fields->get('role'))->forField($result->fields->get('num'));
    $entries = [$admin];

    foreach ([['editor', 0], ['editor', 1], ['viewer', 1]] as [$role, $num]) {
        $entry = EntryModel::factory()->withFieldLayout($layout)->createElement();
        $entry->setFieldValue('role', $role);
        $entry->setFieldValue('num', $num);
        Elements::saveElement($entry);
        $entries[] = $entry;
    }

    [$admin, $editor, $blockedEditor, $viewer] = array_column($entries, 'id');
    $viewerSectionId = $entries[3]->sectionId;
    ElementModel::whereKey($admin)->update(['enabled' => false]);
    $query = fn () => entryQuery()->id([$admin, $editor, $blockedEditor, $viewer])->orderBy('elements.id');

    expect($query()->status(null)->where(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->role('admin'))->orWhere(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->role('editor')->num(0))->ids())->toBe([$admin, $editor])
        ->and($query()->where(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->role('admin'))->ids())->toBe([])
        ->and($query()->whereNot(fn ($q) => $q->role('editor'))->ids())->toBe([$viewer])
        ->and($query()->where('elements.id', $editor)->orWhere(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->sectionId($viewerSectionId))->ids())->toBe([$editor, $viewer])
        ->and(fn () => $query()->where(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->ref('news/slug'))->ids())->toThrow(InvalidArgumentException::class)
        ->and($query()->drafts(null)->where(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->drafts(true))->ids())->toBe([])
        ->and(fn () => $query()->where(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->draftCreator(1))->ids())->toThrow(InvalidArgumentException::class)
        ->and(fn () => $query()->where(fn (Illuminate\Contracts\Database\Query\Builder $q) => $q->structureId(1))->ids())->toThrow(InvalidArgumentException::class)
        ->and($query()->whereNot(fn (Builder $q) => $q->where('elements.id', $editor))->ids())->toBe([$blockedEditor, $viewer])
        ->and($query()->where('elements.id', $viewer)->orWhere(fn (Builder $q) => $q->where('elements.id', $editor))->ids())->toBe([$editor, $viewer]);
});

it('applies params set after the query has run', function () {
    $result = EntryModel::factory()->withField('teaser', PlainText::class, value: 'Beta')->createElementWithFields();
    $beta = $result->element->id;
    [$a, $b] = EntryModel::factory(2)->create()->pluck('id')->all();
    $query = entryQuery()->id([$a, $b, $beta])->orderBy('elements.id');

    expect($query->count())->toBe(3)
        ->and($query->first()->id)->toBe($beta)
        ->and($query->ids())->toBe([$beta, $a, $b])
        ->and($query->teaser('Beta')->ids())->toBe([$beta])
        ->and($query->teaser(null)->id([$a, $b])->count())->toBe(2)
        ->and($query->reorder('elements.id', 'desc')->ids())->toBe([$b, $a]);
});

it('unions element queries and orders the combined result', function () {
    [$a, $b, $c] = EntryModel::factory(3)->create()->pluck('id')->all();
    $query = entryQuery()->id($a)->union(entryQuery()->id([$b, $c]));

    expect($query->count())->toBe(3)
        ->and($query->orderByDesc('id')->get()->pluck('id')->all())->toBe([$c, $b, $a]);
});

it('selects the columns passed to get() unless the query has its own select', function () {
    $result = EntryModel::factory()->withField('teaser', PlainText::class, value: 'Hi')->createElementWithFields();
    $id = $result->element->id;
    $title = $result->element->title;

    expect(entryQuery()->id($id)->asArray()->get(['elements.id', 'teaser'])->all())->toBe([['id' => $id, 'teaser' => 'Hi']])
        ->and(entryQuery()->id($id)->asArray()->select('title')->get(['slug'])->all())->toBe([['title' => $title]])
        ->and(entryQuery()->id($id)->asArray()->addSelect('title as heading')->first(['slug']))->toHaveKeys(['id', 'heading'])
        ->and(entryQuery()->id($id)->first(['title'])->slug)->toBeNull()
        ->and(entryQuery()->id($id)->asArray()->orderBy('elements.id')->cursorPaginate(1, ['elements.id'])->items())->toBe([['id' => $id]]);
});

it('chunks and lazily iterates by id', function () {
    $ids = EntryModel::factory(3)->create()->pluck('id')->all();
    $chunks = [];

    entryQuery()->id($ids)->chunkById(2, function ($chunk) use (&$chunks) {
        $chunks[] = $chunk->pluck('id')->all();
    });

    expect($chunks)->toBe([[$ids[0], $ids[1]], [$ids[2]]])
        ->and(entryQuery()->id($ids)->asArray()->lazyByIdDesc(2)->pluck('id')->all())->toBe(array_reverse($ids));
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
    expect($joins[0]->table)->toBe('elements as elements');
    expect($joins[0]->wheres[0])->toMatchArray([
        'type' => 'Column',
        'first' => 'elements.id',
        'operator' => '=',
        'second' => "$sourceTable.id",
        'boolean' => 'and',
    ]);

    expect($joins[1]->table)->toBe('elements_sites as elements_sites');
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
        $query->getQuery()->leftJoin('search_marker as search_marker', 'search_marker.id', '=', 'elements.id');
    });

    $query->applyBeforeQueryCallbacks();

    expect($clone)->toBeInstanceOf(ElementQuery::class);

    $clone->applyBeforeQueryCallbacks();

    $markerJoins = collect($clone->getQuery()->joins)
        ->filter(fn (JoinClause $join) => $join->table === 'search_marker as search_marker');

    expect($markerJoins)->toHaveCount(1);
});

it('uses clone criteria without changing the original query', function (Closure $factory, array $initial, array $changed, bool $executed, bool $cloneFirst) {
    $this->freezeTime();

    $original = $factory($initial);
    if ($executed) {
        $original->get();
    }
    $clone = clone $original;
    foreach ($changed as $attribute => $value) {
        $clone->$attribute = $value;
    }

    foreach ($cloneFirst ? [$clone, $original] : [$original, $clone] as $query) {
        $criteria = $original->getCriteria();
        $query->get();
        if ($query === $clone) {
            expect($original->getCriteria())->toBe($criteria);
        }
    }

    expect($clone->toRawSql())->toBe($factory($changed)->toRawSql())
        ->and($original->toRawSql())->toBe($factory($initial)->toRawSql());
})->with([
    'asset alt' => [assetQuery(...), ['hasAlt' => true], ['hasAlt' => false]],
    'asset volume' => [assetQuery(...), ['volumeId' => 1], ['volumeId' => 2]],
    'user site' => [userQuery(...), ['affiliatedSiteId' => 1], ['affiliatedSiteId' => 2]],
    'user login' => [userQuery(...), ['lastLoginDate' => '2025-01-01'], ['lastLoginDate' => '2025-02-01']],
    'entry ref' => [entryQuery(...), ['ref' => 'news/first'], ['ref' => 'news/second']],
])->with([false, true])->with([false, true]);

it('preserves extension callback binding and passes the executing query', function () {
    $context = $this;
    $original = entryQuery();
    $clone = null;
    $original->afterQuery(fn ($results) => $results->push('legacy'));
    $original->afterQuery(function ($results, ElementQuery $query) use ($context, &$clone) {
        expect($this)->toBe($context)->and($query)->toBe($clone);

        return $results->push('extension');
    });
    $clone = clone $original;

    expect($clone->applyAfterQueryCallbacks(collect())->all())->toBe(['legacy', 'extension']);
});

it('orders by custom field handles across field layouts', function (string $fieldType, array $values, bool $multipleLayouts) {
    $result = EntryModel::factory()
        ->withField('sortValue', $fieldType, value: $values[0])
        ->createElementWithFields();
    $first = $result->element;
    $factory = EntryModel::factory();

    if ($multipleLayouts) {
        $factory = $factory->withFieldLayout(FieldLayout::factory()->forField($result->fields->get('sortValue')));
    } else {
        $factory = $factory->state(['typeId' => $first->typeId, 'sectionId' => $first->sectionId]);
    }

    $ids = [$first->id];
    foreach (array_slice($values, 1) as $value) {
        $entry = $factory->createElement();
        $entry->setFieldValue('sortValue', $value);
        expect(Elements::saveElement($entry))->toBeTrue();
        $ids[] = $entry->id;
    }

    $query = Entry::find()->id($ids)->orderBy('sortValue');
    $expected = [$ids[1], $ids[0], $ids[2]];

    expect($query->ids())->toBe($expected)
        ->and($query->ids())->toBe($expected)
        ->and((clone $query)->reorder()->orderBy('sortValue', 'desc')->ids())->toBe(array_reverse($expected))
        ->and((clone $query)->inReverse()->ids())->toBe(array_reverse($expected));
})->with([
    'text' => [PlainText::class, ['Bravo', 'Alpha', 'Charlie']],
    'number' => [Number::class, [10, 2, 30]],
])->with([false, true]);

it('orders by rand() and random() on every database', function () {
    $ids = EntryModel::factory(3)->create()->pluck('id')->sort()->values()->all();
    $query = fn () => Entry::find()->id($ids);

    foreach (['rand()', 'RANDOM() desc'] as $orderBy) {
        expect($query()->orderBy($orderBy)->ids())->toEqualCanonicalizing($ids);
    }

    expect($query()->orderBy(['random()' => SORT_ASC])->ids())->toEqualCanonicalizing($ids);
});

it('orders latest() and oldest() by the creation date', function () {
    $ids = EntryModel::factory(3)->create()->pluck('id')->all();

    foreach (['2026-01-02', '2026-01-03', '2026-01-01'] as $i => $day) {
        ElementModel::whereKey($ids[$i])->update(['dateCreated' => "$day 10:00:00"]);
    }

    expect(entryQuery()->id($ids)->latest()->ids())->toBe([$ids[1], $ids[0], $ids[2]])
        ->and(entryQuery()->id($ids)->oldest()->ids())->toBe([$ids[2], $ids[0], $ids[1]]);
});

it('scopes macros to the query class they are registered on', function () {
    EntryQuery::macro('entryOnly', fn () => static::class);
    ElementQuery::macro('everyQuery', fn () => $this::class);

    try {
        expect(entryQuery()->entryOnly())->toBe(EntryQuery::class)
            ->and(UserQuery::hasMacro('entryOnly'))->toBeFalse()
            ->and(fn () => UserElement::find()->entryOnly())->toThrow(BadMethodCallException::class)
            ->and(UserElement::find()->everyQuery())->toBe(UserQuery::class)
            ->and(EntryQuery::hasMacro('everyQuery'))->toBeTrue();
    } finally {
        ElementQuery::flushMacros();
    }

    expect(EntryQuery::hasMacro('entryOnly'))->toBeFalse();
});

it('starts element queries from static calls on element classes, after macros', function () {
    [$a, $b] = EntryModel::factory(2)->create()->pluck('id')->all();
    Entry::macro('macroWins', fn () => 'macro');
    UserElement::macro('userOnly', fn () => 'user');
    Element::macro('everyElement', fn () => $this::class);

    try {
        expect(Entry::query())->toBeInstanceOf(EntryQuery::class)
            ->and(Entry::id([$a, $b])->orderByDesc('elements.id')->ids())->toBe([$b, $a])
            ->and(Entry::whereIn('elements.id', [$a])->ids())->toBe([$a])
            ->and(UserElement::query())->toBeInstanceOf(UserQuery::class)
            ->and(UserElement::admin()->count())->toBe(UserElement::find()->admin()->count())
            ->and(Entry::macroWins())->toBe('macro')
            ->and(fn () => Entry::notAQueryMethod())->toThrow(BadMethodCallException::class)
            ->and(new UserElement()->everyElement())->toBe(UserElement::class)
            ->and(new UserElement()->userOnly())->toBe('user')
            ->and(Entry::hasMacro('userOnly'))->toBeFalse()
            ->and(fn () => new Entry()->userOnly())->toThrow(BadMethodCallException::class);
    } finally {
        Element::flushMacros();
    }

    expect(UserElement::hasMacro('userOnly'))->toBeFalse();
});

it('rejects other SQL expressions passed as strings', function (Closure $query) {
    $entry = EntryModel::factory()->create();

    expect(fn () => $query(Entry::find()->id($entry->id))->all())
        ->toThrow(InvalidArgumentException::class, 'Pass SQL expressions with `expression()`');
})->with([
    'orderBy' => [fn (ElementQuery $query) => $query->orderBy('field(elements.id, 3, 1, 2)')],
    'orderBy array' => [fn (ElementQuery $query) => $query->orderBy(['coalesce(elements.id, 0)' => SORT_DESC])],
    'select' => [fn (ElementQuery $query) => $query->select(['count(*) as total'])->asArray()],
    'groupBy' => [fn (ElementQuery $query) => $query->groupBy('lower(elements.type)')],
]);
