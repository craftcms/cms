<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

test('related elements', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $entries = EntryModel::factory(3)->create();
    foreach ($entries as $entryModel) {
        $entryModel->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $entryModel->entryType->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);
    }

    Fields::invalidateCaches();

    Fields::refreshFields();

    $entry = entryQuery()->firstOrFail();
    $entry->title = 'Test entry';
    $entry->setFieldValue('entriesField', $entries[1]->id);

    Elements::saveElement($entry);

    expect(entryQuery()->count())->toBe(3);
    expect(entryQuery()->relatedTo($entries[1]->id)->count())->toBe(1);
    expect(entryQuery()->notRelatedTo($entries[1]->id)->count())->toBe(2);
    expect(entryQuery()->relatedTo('notavalidelement')->count())->toBe(0);
    expect(entryQuery()->notRelatedTo('notavalidelement')->count())->toBe(3);
});

test('relation fields modify element queries with relation filters', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $entries = EntryModel::factory(3)->create();
    foreach ($entries as $entryModel) {
        $entryModel->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $entryModel->entryType->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);
    }

    Fields::invalidateCaches();
    Fields::refreshFields();

    $entry = entryQuery()->firstOrFail();
    $entry->title = 'Test entry';
    $entry->setFieldValue('entriesField', $entries[1]->id);

    Elements::saveElement($entry);

    /** @var Entries $fieldInstance */
    $fieldInstance = Fields::getFieldById($field->id);
    $elementQuery = entryQuery()->status(null);

    $elementQuery->where(function (Builder $query) use ($fieldInstance, $entries, $elementQuery) {
        Entries::modifyQuery($query, [$fieldInstance], $entries[1]->id, $elementQuery);
    });

    expect($elementQuery->count())->toBe(1);
    expect($elementQuery->one()?->id)->toBe($entry->id);
});

test('AND relation IDs retain field and source site restrictions', function (string $direction, bool $allSites) {
    $fields = Field::factory(2)->create(['type' => Entries::class]);
    $site = Site::first();
    $otherSite = Site::factory()->create();
    $entries = EntryModel::factory(5)->create();
    $references = $entries->take(2);
    $candidates = $entries->skip(2)->values();

    foreach ($candidates as $index => $candidate) {
        foreach ($references as $refIndex => $reference) {
            DB::table(Table::RELATIONS)->insert([
                'sourceId' => $direction === 'targetElement' ? $candidate->id : $reference->id,
                'targetId' => $direction === 'targetElement' ? $reference->id : $candidate->id,
                'fieldId' => $fields[$index === 1 && $refIndex === 1 ? 1 : 0]->id,
                'sourceSiteId' => $refIndex === 0 ? $site->id : ($index === 2 ? $otherSite->id : null),
                'sortOrder' => 1,
                'dateCreated' => now(),
                'dateUpdated' => now(),
                'uid' => (string) str()->uuid(),
            ]);
        }
    }

    $criteria = [
        $direction => ['and', ...$references->modelKeys()],
        'field' => $fields[0]->id,
        'sourceSite' => $allSites ? null : $site->id,
    ];
    $expected = $allSites ? [$candidates[0]->id, $candidates[2]->id] : [$candidates[0]->id];

    expect(entryQuery()->id($candidates->modelKeys())->relatedTo($criteria)->ids())->toEqualCanonicalizing($expected)
        ->and(entryQuery()->id($candidates->modelKeys())->notRelatedTo($criteria)->ids())
        ->toEqualCanonicalizing(array_values(array_diff($candidates->modelKeys(), $expected)));
})->with(['sourceElement', 'targetElement', 'element'])->with([false, true]);

test('relations stored for any of the queried sites match', function (bool $allSites) {
    $relationField = Field::factory()->create(['type' => Entries::class]);
    $matrixField = Field::factory()->create(['type' => Matrix::class]);
    Fields::refreshFields();

    $site = Site::first();
    $otherSite = Site::factory()->create();
    [$source, $target, $owner, $nestedTarget] = EntryModel::factory(4)->create();
    $nested = EntryModel::factory()->create([
        'primaryOwnerId' => $owner->id,
        'fieldId' => $matrixField->id,
    ]);

    // Both relations are stored for the second site only, so they match only when every queried site is checked
    foreach ([[$source, $target], [$nested, $nestedTarget]] as [$relationSource, $relationTarget]) {
        DB::table(Table::RELATIONS)->insert([
            'fieldId' => $relationField->id,
            'sourceId' => $relationSource->id,
            'sourceSiteId' => $otherSite->id,
            'targetId' => $relationTarget->id,
            'sortOrder' => 1,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => (string) str()->uuid(),
        ]);
    }

    $query = fn () => entryQuery()->siteId($allSites ? '*' : [$site->id, $otherSite->id]);

    expect($query()->relatedTo($target->id)->ids())->toBe([$source->id])
        ->and($query()->relatedTo(['sourceElement' => $owner->id, 'field' => $matrixField->id])->ids())->toBe([$nestedTarget->id])
        ->and($query()->relatedTo(['targetElement' => $nestedTarget->id, 'field' => $matrixField->id])->ids())->toBe([$owner->id]);
})->with([false, true]);

test('reference-tracking fields match when named, and all fields match with *', function () {
    actingAs(User::findOne());

    $target = EntryModel::factory()->createElement();
    $linkSource = EntryModel::factory()
        ->withField('linkTarget', Link::class, ['types' => ['entry']], value: ['type' => 'entry', 'value' => "{entry:$target->id@$target->siteId:url}"])
        ->createElementWithFields()
        ->element;
    $relationSource = EntryModel::factory()
        ->withField('entriesField', Entries::class, value: [$target->id])
        ->createElementWithFields()
        ->element;

    expect(entryQuery()->relatedTo(['targetElement' => $target, 'field' => 'linkTarget'])->ids())->toBe([$linkSource->id])
        ->and(entryQuery()->relatedTo(['element' => $target, 'field' => 'linkTarget'])->ids())->toBe([$linkSource->id])
        ->and(entryQuery()->relatedTo(['sourceElement' => $linkSource, 'field' => 'linkTarget'])->ids())->toBe([$target->id])
        ->and(entryQuery()->relatedTo(['targetElement' => $target])->ids())->toBe([$relationSource->id])
        ->and(entryQuery()->relatedTo(['targetElement' => $target, 'field' => '*'])->ids())->toEqualCanonicalizing([$linkSource->id, $relationSource->id])
        ->and(entryQuery()->relatedTo(['sourceElement' => $linkSource, 'field' => '*'])->ids())->toBe([$target->id])
        ->and(entryQuery()->notRelatedTo(['targetElement' => $target, 'field' => 'linkTarget'])->ids())->toEqualCanonicalizing([$target->id, $relationSource->id]);
});
