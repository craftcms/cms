<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Events\ElementsEagerLoading;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

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
    Sections::refreshSections();

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

        $relatedEntry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->create();

        $entryElement = entryQuery()->id($model->id)->firstOrFail();
        $entryElement->title = 'Test entry '.$model->id;
        $entryElement->setFieldValue('entriesField', [$relatedEntry->id]);
        Elements::saveElement($entryElement);
    }

    ElementCaches::invalidateAll();

    $this->entryModels = $entryModels;
});

test('with loads relations once when creating results', function (string $mode) {
    $entry = entryQuery()->id($this->entryModels->first()->id)->first();
    expect($entry->entriesField)->toBeInstanceOf(ElementQuery::class);
    $relatedId = $entry->entriesField->ids()[0];
    $row = (object) entryQuery()->id($entry->id)->asArray()->first();
    $expectedId = $mode === 'provisional'
        ? app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true)->id
        : $entry->id;
    $query = entryQuery()->id($entry->id)->with($mode === 'nested' ? 'entriesField.authors as writers' : 'entriesField');
    if ($mode === 'override') {
        $query->setResultOverride([$entry]);
    }

    Event::fake([ElementsEagerLoading::class]);
    $hydratedRelations = [];
    Event::listen(ElementsHydrated::class, function (ElementsHydrated $event) use (&$hydratedRelations) {
        foreach ($event->elements as $element) {
            if ($element->hasEagerLoadedElements('entriesField')) {
                $hydratedRelations[$element->id] = $element->entriesField;
            }
        }
    });
    DB::enableQueryLog();
    DB::flushQueryLog();

    $result = match ($mode) {
        'hydrate' => $query->hydrate([$row])[0],
        'getModels' => $query->getModels()[0],
        'cursor' => $query->cursor()->first(),
        'provisional' => $query->withProvisionalDrafts()->first(),
        default => $query->first(),
    };

    expect($result->id)->toBe($expectedId)
        ->and($result->entriesField)->toBeInstanceOf(ElementCollection::class)
        ->and($result->entriesField->pluck('id')->all())->toBe([$relatedId]);
    Event::assertDispatchedTimes(ElementsEagerLoading::class, $mode === 'nested' ? 2 : 1);
    expect(collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'postDate') && in_array($relatedId, $query['bindings'], true)))->toHaveCount(1);
    if (! in_array($mode, ['override', 'cursor'])) {
        expect($hydratedRelations[$expectedId])->toBe($result->entriesField);
    }
    if ($mode === 'nested') {
        Event::assertDispatched(fn (ElementsEagerLoading $event) => $event->with[0]->alias === 'writers');
        $queries = DB::getQueryLog();
        $result->entriesField->first()->getAuthors();
        expect(DB::getQueryLog())->toBe($queries);
    }
})->with(['get', 'getModels', 'hydrate', 'override', 'cursor', 'provisional', 'nested']);

test('with skips eager loading for array and empty results', function (bool $empty) {
    Event::fake([ElementsEagerLoading::class]);
    $query = entryQuery()->id($empty ? 0 : $this->entryModels->first()->id)->with('entriesField');

    expect($empty ? $query->all() : $query->asArray()->first())->toBeArray();
    Event::assertNotDispatched(ElementsEagerLoading::class);
})->with([false, true]);

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

test('relation queries eagerly load automatically', function () {
    $results = entryQuery()->section('blog')->get();

    $queryCountWithoutAutomaticEagerLoading = 0;
    $queryCountWithAutomaticEagerLoading = 0;

    DB::listen(function ($query) use (&$queryCountWithoutAutomaticEagerLoading, &$queryCountWithAutomaticEagerLoading) {
        $queryCountWithoutAutomaticEagerLoading++;
        $queryCountWithAutomaticEagerLoading++;
    });

    foreach ($results as $result) {
        $result->entriesField->eagerly(false)->all();
    }

    $queryCountWithoutAutomaticEagerLoadingResults = $queryCountWithoutAutomaticEagerLoading;

    $results = entryQuery()->section('blog')->get();

    $queryCountWithAutomaticEagerLoading = 0;

    foreach ($results as $result) {
        $result->entriesField->all();
    }

    expect($queryCountWithAutomaticEagerLoading)->toBeLessThan($queryCountWithoutAutomaticEagerLoadingResults);
});

test('automatic eager loading can be disabled globally and explicitly enabled per query', function () {
    Cms::config()->autoEagerLoadElements = false;

    try {
        $results = entryQuery()->section('blog')->get();
        $eagerLoadingEvents = 0;
        Event::listen(ElementsEagerLoading::class, function () use (&$eagerLoadingEvents) {
            $eagerLoadingEvents++;
        });

        foreach ($results as $result) {
            $result->entriesField->first();
        }

        expect($eagerLoadingEvents)->toBe(0);

        $results = entryQuery()->section('blog')->get();

        foreach ($results as $result) {
            $result->entriesField->eagerly()->first();
        }

        expect($eagerLoadingEvents)->toBe(1);
    } finally {
        Cms::config()->autoEagerLoadElements = true;
    }
});

test('automatic eager loading keeps limited and complete results separate', function () {
    $sourceModels = $this->entryModels->take(2);

    foreach ($sourceModels as $sourceModel) {
        $source = entryQuery()->id($sourceModel->id)->firstOrFail();
        $relatedIds = $source->entriesField->eagerly(false)->ids();
        $additionalRelated = EntryModel::factory()
            ->forSection($sourceModel->section)
            ->forEntryType($sourceModel->entryType)
            ->create();

        $source->setFieldValue('entriesField', [...$relatedIds, $additionalRelated->id]);
        Elements::saveElement($source);
    }

    ElementCaches::invalidateAll();

    $sources = entryQuery()
        ->id($sourceModels->pluck('id')->all())
        ->fixedOrder()
        ->all();

    expect($sources[0]->entriesField->one())->not->toBeNull()
        ->and($sources[1]->entriesField->all())->toHaveCount(2);
});

test('automatic eager loading keeps different criteria separate', function () {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();

    expect($sources[0]->entriesField->title('Does not exist')->all())->toBe([])
        ->and($sources[1]->entriesField->all())->toHaveCount(1);
});

test('automatic eager loading falls back for direct query builder changes', function (string $mode) {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();
    $query = $sources[0]->entriesField;

    match ($mode) {
        'where' => $query->where('elements.id', -1),
        'orderBy' => $query->orderByDesc('elements.id'),
        'limit' => $query->limit(1),
        'callback' => $query->beforeQuery(fn (ElementQuery $query) => $query->where('elements.id', -1)),
    };

    $eagerLoadingEvents = 0;
    Event::listen(ElementsEagerLoading::class, function () use (&$eagerLoadingEvents) {
        $eagerLoadingEvents++;
    });

    expect($query->all())->toHaveCount(in_array($mode, ['where', 'callback'], true) ? 0 : 1)
        ->and($eagerLoadingEvents)->toBe(0);
})->with(['where', 'orderBy', 'limit', 'callback']);

test('automatic eager loading preserves result overrides', function () {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();
    $query = $sources[0]->entriesField;
    $related = (clone $query)->eagerly(false)->one();
    $query->setResultOverride([$related]);
    $eagerLoadingEvents = 0;
    Event::listen(ElementsEagerLoading::class, function () use (&$eagerLoadingEvents) {
        $eagerLoadingEvents++;
    });

    expect($query->all())->toBe([$related])
        ->and($eagerLoadingEvents)->toBe(0);
});

test('automatic eager loading preserves array results', function () {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();
    $eagerLoadingEvents = 0;
    Event::listen(ElementsEagerLoading::class, function () use (&$eagerLoadingEvents) {
        $eagerLoadingEvents++;
    });

    $related = $sources[0]->entriesField->asArray()->all();

    expect($related)->not->toBeEmpty()
        ->and($related[0])->toBeArray()
        ->and($eagerLoadingEvents)->toBe(0);
});

test('automatic eager loading preserves custom selected columns', function () {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();
    $eagerLoadingEvents = 0;
    Event::listen(ElementsEagerLoading::class, function () use (&$eagerLoadingEvents) {
        $eagerLoadingEvents++;
    });

    $related = $sources[0]->entriesField->all(['elements.id', 'elements_sites.siteId']);

    expect($related)->not->toBeEmpty()
        ->and($eagerLoadingEvents)->toBe(0);
});

test('automatic eager loading applies after-query callbacks', function () {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();
    $afterQueryCalls = 0;

    $related = $sources[0]->entriesField
        ->afterQuery(function ($result) use (&$afterQueryCalls) {
            $afterQueryCalls++;

            return $result;
        })
        ->one();

    expect($related)->not->toBeNull()
        ->and($afterQueryCalls)->toBe(1);
});

test('automatic eager loading applies limits to each source', function () {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();

    $relatedIds = collect($sources)
        ->map(fn (Entry $source) => $source->entriesField->one()?->id)
        ->all();

    expect($relatedIds)->not->toContain(null)
        ->and(array_unique($relatedIds))->toHaveCount(2);
});

test('automatic eager loading keeps counts and elements separate', function () {
    $sources = entryQuery()
        ->id($this->entryModels->take(2)->pluck('id')->all())
        ->fixedOrder()
        ->all();

    expect($sources[0]->entriesField->count())->toBe(1)
        ->and($sources[1]->entriesField->all())->toHaveCount(1);
});

test('automatic eager loading stores empty results for the cohort', function () {
    $sourceModels = $this->entryModels->take(2);
    $sourceWithoutRelations = entryQuery()->id($sourceModels->last()->id)->firstOrFail();
    $sourceWithoutRelations->setFieldValue('entriesField', []);
    Elements::saveElement($sourceWithoutRelations);
    ElementCaches::invalidateAll();

    $sources = entryQuery()
        ->id($sourceModels->pluck('id')->all())
        ->fixedOrder()
        ->all();
    $eagerLoadingEvents = 0;
    Event::listen(ElementsEagerLoading::class, function () use (&$eagerLoadingEvents) {
        $eagerLoadingEvents++;
    });

    expect($sources[0]->entriesField->all())->toHaveCount(1)
        ->and($sources[1]->entriesField->all())->toBe([])
        ->and($eagerLoadingEvents)->toBe(1);
});

test('automatic eager loading skips single-element cohorts', function () {
    $source = entryQuery()->id($this->entryModels->first()->id)->firstOrFail();
    $eagerLoadingEvents = 0;
    Event::listen(ElementsEagerLoading::class, function () use (&$eagerLoadingEvents) {
        $eagerLoadingEvents++;
    });

    expect($source->entriesField->all())->toHaveCount(1)
        ->and($eagerLoadingEvents)->toBe(0);
});

test('eagerly lazy loads nested entry fields for each owner', function () {
    $nestedEntryType = EntryType::factory()
        ->withFieldLayout()
        ->create([
            'name' => 'Nested Block',
            'handle' => 'nestedBlock',
            'hasTitleField' => true,
        ]);

    $ownerEntryType = EntryType::factory()
        ->withFieldLayout()
        ->create([
            'name' => 'Owner',
            'handle' => 'owner',
            'hasTitleField' => true,
        ]);

    $section = Section::factory()
        ->withEntryTypes($ownerEntryType, $nestedEntryType)
        ->create([
            'handle' => 'matrixBlog',
        ]);

    $firstOwnerResult = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerEntryType)
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$nestedEntryType->id]])
        ->createElementWithFields(['title' => 'Owner A']);

    $field = app(Fields::class)->getFieldById($firstOwnerResult->field('matrixField')->id);
    $firstOwner = entryQuery()->id($firstOwnerResult->element->id)->one();
    $secondOwner = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerEntryType)
        ->createElement(['title' => 'Owner B']);

    DB::table(Table::ELEMENTS)
        ->where('id', $secondOwner->id)
        ->update(['fieldLayoutId' => $firstOwner->fieldLayoutId]);

    $createNestedEntry = function (Entry $owner, string $title) use ($field, $nestedEntryType, $section) {
        $entry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($nestedEntryType)
            ->title($title)
            ->createElement([
                'fieldId' => $field->id,
                'primaryOwnerId' => $owner->id,
            ]);

        DB::table(Table::ENTRIES)
            ->where('id', $entry->id)
            ->update(['sectionId' => null]);

        DB::table(Table::ELEMENTS_OWNERS)->insert([
            'elementId' => $entry->id,
            'ownerId' => $owner->id,
            'sortOrder' => 1,
        ]);
    };

    $createNestedEntry($firstOwner, 'First nested block');
    $createNestedEntry($secondOwner, 'Second nested block');

    $owners = entryQuery()
        ->section('matrixBlog')
        ->typeId($ownerEntryType->id)
        ->status(null)
        ->orderBy('elements.id')
        ->all();

    $nestedTitles = collect($owners)
        ->map(fn ($owner) => $owner->matrixField->one()?->title)
        ->all();

    expect($nestedTitles)->toBe([
        'First nested block',
        'Second nested block',
    ]);
});
