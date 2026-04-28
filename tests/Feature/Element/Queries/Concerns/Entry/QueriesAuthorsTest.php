<?php

use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Str;

dataset('falsy-query-values', [
    0,
    '0',
]);

it('can query entries by authors', function () {
    $author1 = User::factory()->create();
    $author2 = User::factory()->create();

    Entry::factory()
        ->hasAttached($author1, ['sortOrder' => 0], 'authors')
        ->create();

    Entry::factory()
        ->hasAttached($author2, ['sortOrder' => 0], 'authors')
        ->create();

    expect(entryQuery()->count())->toBe(2);

    Edition::set(Edition::Solo);

    // Does nothing when edition is solo
    expect(entryQuery()->authorId($author1->id)->count())->toBe(2);

    Edition::set(Edition::Pro);

    expect(entryQuery()->authorId($author1->id)->count())->toBe(1);
    expect(entryQuery()->authorId($author2->id)->count())->toBe(1);
    expect(entryQuery()->authorId([$author1->id, $author2->id])->count())->toBe(2);
    expect(entryQuery()->authorId(implode(', ', [$author1->id, $author2->id]))->count())->toBe(2);
    expect(entryQuery()->authorId('not '.$author1->id)->count())->toBe(1);
});

it('treats falsy author IDs as explicit filters', function (mixed $authorId) {
    $author1 = User::factory()->create();
    $author2 = User::factory()->create();

    Entry::factory()
        ->hasAttached($author1, ['sortOrder' => 0], 'authors')
        ->create();

    Entry::factory()
        ->hasAttached($author2, ['sortOrder' => 0], 'authors')
        ->create();

    Edition::set(Edition::Pro);

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->authorId($authorId)->count())->toBe(0);
})->with('falsy-query-values');

it('can query entries by author groups', function () {
    $author1 = User::factory()
        ->hasAttached(
            $userGroup1 = UserGroup::factory()->create(),
            ['dateCreated' => now(), 'dateUpdated' => now(), 'uid' => Str::uuid()->toString()],
            'userGroups',
        )
        ->create();

    $author2 = User::factory()
        ->hasAttached(
            $userGroup2 = UserGroup::factory()->create(),
            ['dateCreated' => now(), 'dateUpdated' => now(), 'uid' => Str::uuid()->toString()],
            'userGroups',
        )
        ->create();

    Entry::factory()
        ->hasAttached($author1, ['sortOrder' => 0], 'authors')
        ->create();

    Entry::factory()
        ->hasAttached($author2, ['sortOrder' => 0], 'authors')
        ->create();

    expect(entryQuery()->count())->toBe(2);

    Edition::set(Edition::Solo);

    // Does nothing when edition is solo
    expect(entryQuery()->authorGroupId($userGroup1->id)->count())->toBe(2);

    Edition::set(Edition::Pro);

    expect(entryQuery()->authorGroupId($userGroup1->id)->count())->toBe(1);
    expect(entryQuery()->authorGroupId($userGroup2->id)->count())->toBe(1);
    expect(entryQuery()->authorGroupId([$userGroup1->id, $userGroup2->id])->count())->toBe(2);
    expect(entryQuery()->authorGroupId(implode(', ', [$userGroup1->id, $userGroup2->id]))->count())->toBe(2);
    expect(entryQuery()->authorGroupId('not '.$userGroup1->id)->count())->toBe(1);

    expect(entryQuery()->authorGroup('*')->count())->toBe(2);
    expect(entryQuery()->authorGroup($userGroup1->handle)->count())->toBe(1);
    expect(entryQuery()->authorGroup($userGroup2->handle)->count())->toBe(1);
    expect(entryQuery()->authorGroup('not '.$userGroup2->handle)->count())->toBe(1);
    expect(entryQuery()->authorGroup([$userGroup1->handle, $userGroup2->handle])->count())->toBe(2);
    expect(entryQuery()->authorGroup(['not', $userGroup1->handle])->count())->toBe(1);
    expect(entryQuery()->authorGroup(['not', $userGroup1->handle, $userGroup2->handle])->count())->toBe(0);
    expect(entryQuery()->authorGroup(implode(', ', [$userGroup1->handle, $userGroup2->handle]))->count())->toBe(2);
});

it('treats falsy author group IDs as explicit filters', function (mixed $groupId) {
    $author1 = User::factory()
        ->hasAttached(
            UserGroup::factory()->create(),
            ['dateCreated' => now(), 'dateUpdated' => now(), 'uid' => Str::uuid()->toString()],
            'userGroups',
        )
        ->create();

    $author2 = User::factory()
        ->hasAttached(
            UserGroup::factory()->create(),
            ['dateCreated' => now(), 'dateUpdated' => now(), 'uid' => Str::uuid()->toString()],
            'userGroups',
        )
        ->create();

    Entry::factory()
        ->hasAttached($author1, ['sortOrder' => 0], 'authors')
        ->create();

    Entry::factory()
        ->hasAttached($author2, ['sortOrder' => 0], 'authors')
        ->create();

    Edition::set(Edition::Pro);

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->authorGroupId($groupId)->count())->toBe(0);
})->with('falsy-query-values');
