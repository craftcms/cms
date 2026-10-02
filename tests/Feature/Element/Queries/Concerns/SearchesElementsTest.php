<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;

test('unscored search filters candidates without materializing matching index rows', function () {
    $entry = EntryModel::factory()->title('Orchard')->slug('orchard')->indexed()->create();
    EntryModel::factory()->title('Orchard elsewhere')->indexed()->create();

    DB::enableQueryLog();
    DB::flushQueryLog();

    $results = entryQuery()->id($entry->id)->search('orchard')->ids();
    $queries = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains($query['query'], 'searchindex'));

    DB::disableQueryLog();

    expect($results)->toBe([$entry->id]);
    expect($queries)->toHaveCount(1);
    expect($queries->first()['query'])->toContain('exists');
});

test('unscored search retains site filters and observes index changes', function () {
    $entry = EntryModel::factory()->title('Orchard')->indexed()->create();
    $siteId = Sites::getCurrentSite()->id;
    $otherSite = Site::factory()->create();
    Sites::refreshSites();

    DB::table(Table::SEARCHINDEX)->insert([
        'elementId' => $entry->id,
        'siteId' => $otherSite->id,
        'attribute' => 'title',
        'fieldId' => '0',
        'keywords' => ' berry ',
        ...(DB::isPgsql() ? ['keywords_vector' => ' berry '] : []),
    ]);

    expect(entryQuery()->siteId($siteId)->search('berry')->count())->toBe(0);
    expect(entryQuery()->siteId([$siteId, $otherSite->id])->search('berry')->ids())->toBe([$entry->id]);

    DB::table(Table::SEARCHINDEX)->where('elementId', $entry->id)->update([
        'keywords' => ' apple ',
        ...(DB::isPgsql() ? ['keywords_vector' => ' apple '] : []),
    ]);

    expect(entryQuery()->search('orchard')->count())->toBe(0);
    expect(entryQuery()->search('apple')->ids())->toBe([$entry->id]);
});

test('unscored search paginates unique matching elements', function () {
    $entries = EntryModel::factory()->title('Orchard')->slug('orchard')->indexed()->count(3)->create();

    $page = entryQuery()->search('orchard')->orderBy('id')->paginate(2, page: 2);

    expect($page->total())->toBe(3);
    expect($page->items())->toHaveCount(1);
    expect($page->items()[0]->id)->toBe($entries->last()->id);
});

test('unscored candidate filtering preserves keyword conditions', function (string $term) {
    EntryModel::factory()->title('Orchard apples')->slug('green-fruit')->indexed()->create();
    EntryModel::factory()->title('Berry fruit')->slug('orchard')->indexed()->create();
    EntryModel::factory()->title('Unrelated')->slug('other')->indexed()->create();

    $query = entryQuery()->search($term);
    $search = Search::createDbQuery($term, $query);
    $expected = $search === false ? [] : $search->pluck('elementId')->unique()->sort()->values()->all();

    expect($query->ids())->toEqualCanonicalizing($expected);
    expect($query->count())->toBe(count($expected));
})->with([
    'word' => 'orchard',
    'prefix' => 'orch*',
    'substring' => '*chard*',
    'phrase' => '"orchard apples"',
    'conjunction' => 'orchard apples',
    'disjunction' => 'orchard OR berry',
    'exclusion' => 'fruit -berry',
    'attribute' => 'title:orchard',
    'exact attribute' => 'slug::orchard',
    'attribute exclusion' => 'title:-orchard',
    'attribute disjunction' => 'title:orchard OR slug:orchard',
    'missing' => 'nomatchingword',
]);

test('search', function () {
    $entry1 = EntryModel::factory()->title('Foo')->indexed()->create();
    $entry2 = EntryModel::factory()->title('Bar')->indexed()->create();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->search('Foo')->count())->toBe(1);
});

test('search with score', function () {
    $entry1 = EntryModel::factory()->title('Foo')->indexed()->create();
    $entry2 = EntryModel::factory()->title('Bar')->slug('Foo')->indexed()->create();

    expect(entryQuery()->orderBy('score')->count())->toBe(2);
    expect(entryQuery()->search('Foo')->orderBy('score')->count())->toBe(2);

    $results = entryQuery()->search('Foo')->orderBy('score')->get();

    expect($results[0]->id)->toBe($entry2->id);
    expect($results[1]->id)->toBe($entry1->id);

    $results = entryQuery()->search('Foo')->orderByDesc('score')->get();

    expect($results[0]->id)->toBe($entry1->id);
    expect($results[1]->id)->toBe($entry2->id);

    $results = entryQuery()->search('Foo')->orderBy('score')->inReverse()->get();

    expect($results[0]->id)->toBe($entry1->id);
    expect($results[1]->id)->toBe($entry2->id);
});

test('asset search with score', function () {
    $asset = AssetModel::factory()->create([
        'filename' => 'foo-searchable.jpg',
    ]);

    Search::indexElementAttributes(AssetElement::find()->id($asset->id)->one());

    $results = assetQuery()->search('foo-searchable')->orderByDesc('score')->get();

    expect($results)->toHaveCount(1);
    expect($results[0]->id)->toBe($asset->id);
});
