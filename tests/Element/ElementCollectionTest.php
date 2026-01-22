<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    $entry = EntryModel::factory()->create();
    $entry->element->siteSettings()->update(['title' => 'Theories of life']);

    EntryModel::factory(5)->create();
});

test('find', function () {
    $collection = Entry::find()->get();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $first = $collection->first();

    expect($first)->toBeInstanceOf(Entry::class);
    expect($first)->toBe($collection->find($first));
    expect($collection->find(User::findOne()))->toBeNull();
    expect([$first])->toBe($collection->find([$first->id])->all());
    expect($collection->find([-1])->isEmpty())->toBeTrue();
    expect($first)->toBe($collection->find($first->id));
    expect($collection->find(-1))->toBeNull();
});

test('contains', function () {
    $collection = Entry::find()->get();

    expect($collection)->toBeInstanceOf(ElementCollection::class);
    expect($collection->contains('title', 'Theories of life'))->toBeTrue();
    expect($collection->contains(fn (Entry $entry) => $entry->title === 'Theories of life'))->toBeTrue();
    expect($collection->contains(fn (Entry $entry) => false))->toBeFalse();

    $first = $collection->first();

    expect($first)->toBeInstanceOf(Entry::class);
    expect($collection->contains($first))->toBeTrue();
    expect($collection->contains(User::find()->one()))->toBeFalse();
    expect($collection->contains($first->id))->toBeTrue();
    expect($collection->contains(-1))->toBeFalse();
    expect($collection->contains('title'))->toBeFalse();
});

test('ids', function () {
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $ids = $collection->map(fn (Entry $entry) => $entry->id)->all();
    expect($collection->ids()->all())->toBe($ids);
});

test('merge', function () {
    /** @var ElementCollection<Entry|User> $collection */
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $first = $collection->first();
    expect($first)->toBeInstanceOf(Entry::class);

    $user = User::find()->one();
    expect($user)->toBeInstanceOf(User::class);

    $merged = $collection->merge([$user]);
    expect($merged->contains($first))->toBeTrue();
    expect($merged->contains($user))->toBeTrue();
    expect($merged)->toHaveCount($collection->count() + 1);
});

test('map', function () {
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $mapped = $collection->map(fn (Entry $entry) => new Entry);
    expect($mapped)->toBeInstanceOf(ElementCollection::class);

    $mapped = $collection->map(fn (Entry $entry) => $entry->id);
    expect($mapped::class)->toBe(Collection::class);
});

test('mapWithKeys', function () {
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $mapped = $collection->mapWithKeys(fn (Entry $entry, int|string $key) => [$entry->id => new Entry]);
    expect($mapped)->toBeInstanceOf(ElementCollection::class);

    $mapped = $collection->mapWithKeys(fn (Entry $entry, int|string $key) => [$entry->id => $entry->id]);
    expect($mapped)->toBeInstanceOf(Collection::class)
        ->not->toBeInstanceOf(ElementCollection::class);
});

test('fresh', function () {
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $collection->each(function (Entry $entry) {
        $entry->title .= 'edit';
    });
    expect($collection->contains(fn (Entry $entry) => ! str_ends_with((string) $entry->title, 'edit')))->toBeFalse();

    $fresh = $collection->fresh();
    expect($fresh)->toHaveCount($collection->count());
    expect($fresh->contains(fn (Entry $entry) => ! str_ends_with((string) $entry->title, 'edit')))->toBeTrue();
});

test('diff', function () {
    $collection1 = Entry::find()->limit(4)->collect();
    expect($collection1)->toBeInstanceOf(ElementCollection::class);
    expect($collection1)->toHaveCount(4);

    $collection2 = Entry::find()->offset(3)->collect();
    expect($collection2)->toBeInstanceOf(ElementCollection::class);
    expect($collection2->isNotEmpty())->toBeTrue();

    $diff = $collection1->diff($collection2->all());
    expect($diff)->toHaveCount(3);
});

test('intersect', function () {
    $collection1 = Entry::find()->limit(4)->collect();
    expect($collection1)->toBeInstanceOf(ElementCollection::class);
    expect($collection1)->toHaveCount(4);

    $collection2 = Entry::find()->offset(3)->collect();
    expect($collection2)->toBeInstanceOf(ElementCollection::class);
    expect($collection2->isNotEmpty())->toBeTrue();

    $intersect = $collection1->intersect($collection2->all());
    expect($intersect)->toHaveCount(1);
});

test('unique', function () {
    $collection = Entry::find()->limit(4)->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $count = $collection->count();
    $collection->push(...$collection->all());
    expect($collection)->toHaveCount($count * 2);

    $unique = $collection->unique();
    expect($unique)->toHaveCount($count);
});

test('only', function () {
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);
    expect($collection)->not->toHaveCount(1);

    $first = $collection->first();
    expect($first)->toBeInstanceOf(Entry::class);
    expect($collection->only([$first->id]))->toHaveCount(1);
    expect($collection->only($first->id))->toHaveCount(1);
});

test('except', function () {
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);

    $count = $collection->count();
    $first = $collection->first();
    expect($first)->toBeInstanceOf(Entry::class);
    expect($collection->except([$first->id]))->toHaveCount($count - 1);
    expect($collection->except($first->id))->toHaveCount($count - 1);
});

test('toBaseMethods', function () {
    $collection = Entry::find()->collect();
    expect($collection)->toBeInstanceOf(ElementCollection::class);
    expect($collection->countBy(fn (Entry $entry) => $entry->sectionId)::class)->toBe(Collection::class);
    expect($collection->collapse()::class)->toBe(Collection::class);
    expect($collection->flatten(1)::class)->toBe(Collection::class);
    expect($collection->keys()::class)->toBe(Collection::class);
    expect($collection->pad(100, null)::class)->toBe(Collection::class);
    expect($collection->pluck('title')::class)->toBe(Collection::class);
    expect($collection->zip($collection->ids())::class)->toBe(Collection::class);
});
