<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $section = Section::factory()->create([
        'handle' => 'blog',
    ]);

    $entryType = EntryType::factory()->create([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    app(Fields::class)->invalidateCaches();
    app(Fields::class)->refreshFields();

    $entryModels = EntryModel::factory(10)->forSection($section)->forEntryType($entryType)->create();

    foreach ($entryModels as $model) {
        $model->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $relatedEntry = EntryModel::factory()->create();

        $entryElement = entryQuery()->id($model->id)->firstOrFail();
        $entryElement->title = 'Test entry '.$model->id;
        $entryElement->setFieldValue('entriesField', [$relatedEntry->id]);
        Elements::saveElement($entryElement);
    }

    ElementCaches::invalidateAll();

    $this->entryModels = $entryModels;
});

test('with', function () {
    $result = entryQuery()->id($this->entryModels->first()->id)->first();

    expect($result->entriesField)->toBeInstanceOf(ElementQuery::class);

    $result = entryQuery()->id($this->entryModels->first()->id)->with('entriesField')->first();

    expect($result->entriesField)->toBeInstanceOf(ElementCollection::class);
});

test('andWith', function () {
    $result = entryQuery()->id($this->entryModels->first()->id)->andWith('entriesField')->first();

    expect($result->entriesField)->toBeInstanceOf(ElementCollection::class);
});

test('andWith supports criteria tuples', function () {
    $result = entryQuery()->id($this->entryModels->first()->id)->andWith(['entriesField', ['status' => null]])->first();

    expect($result->entriesField)->toBeInstanceOf(ElementCollection::class);
});

test('andWith eager loads entry authors for hydrated query results', function () {
    $author = CraftCms\Cms\User\Models\User::factory()->createElement(['fullName' => 'Indexed Author']);
    $entry = entryQuery()->id($this->entryModels->first()->id)->firstOrFail();

    DB::table(Table::ENTRIES_AUTHORS)->where('entryId', $entry->id)->delete();

    DB::table(Table::ENTRIES_AUTHORS)->insert([
        'entryId' => $entry->id,
        'authorId' => $author->id,
        'sortOrder' => 1,
    ]);

    $result = entryQuery()
        ->id($entry->id)
        ->andWith(['authors', ['status' => null]])
        ->firstOrFail();

    expect($result->getAuthors())->toHaveCount(1)
        ->and($result->getAuthors()[0]->id)->toBe($author->id);
});

test('eagerly', function () {
    $results = entryQuery()->section('blog')->get();

    $queryCountWithoutEagerly = 0;
    $queryCountWithEagerly = 0;

    DB::listen(function ($query) use (&$queryCountWithoutEagerly, &$queryCountWithEagerly) {
        $queryCountWithoutEagerly++;
        $queryCountWithEagerly++;
    });

    foreach ($results as $result) {
        $result->entriesField->first();
    }

    $queryCountWithoutEagerlyResults = $queryCountWithoutEagerly;

    $results = entryQuery()->section('blog')->get();

    $queryCountWithEagerly = 0;

    foreach ($results as $result) {
        $result->entriesField->eagerly()->first();
    }

    expect($queryCountWithEagerly)->toBeLessThan($queryCountWithoutEagerlyResults);
});
