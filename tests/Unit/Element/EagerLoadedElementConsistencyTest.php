<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;

/**
 * Ensures that getEagerLoadedElements()/hasEagerLoadedElements() stay in sync with the
 * dedicated getter methods (getOwner(), getPrimaryOwner(), getUploader(), getAuthor(),
 * getAuthors(), getPhoto()) once eager-loaded elements have been applied to an element.
 *
 * Prior to the fix, setEagerLoadedElements() implementations for these special-cased handles
 * would return early rather than also calling their parent implementation, meaning
 * getEagerLoadedElements()/hasEagerLoadedElements() wouldn't reflect elements that had
 * clearly been eager-loaded (as evidenced by the dedicated getter methods).
 *
 * @see https://github.com/craftcms/cms/pull/19468
 */
test('owner and primaryOwner eager-loading stays in sync with the dedicated getters', function (string $handle) {
    $owner = new Entry(['id' => 100]);
    $nested = new Entry(['id' => 1]);
    $plan = new EagerLoadPlan(handle: $handle);

    $nested->setEagerLoadedElements($handle, [$owner], $plan);

    expect($nested->hasEagerLoadedElements($handle))->toBeTrue();

    $eagerLoaded = $nested->getEagerLoadedElements($handle);
    expect($eagerLoaded)->toBeInstanceOf(ElementCollection::class)
        ->and($eagerLoaded->all())->toBe([$owner]);

    $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
    expect($getter)->toBe($owner);
})->with(['owner', 'primaryOwner']);

test('owner and primaryOwner eager-loading with no owner stays in sync with the dedicated getters', function (string $handle) {
    $nested = new Entry(['id' => 1]);
    $plan = new EagerLoadPlan(handle: $handle);

    $nested->setEagerLoadedElements($handle, [], $plan);

    expect($nested->hasEagerLoadedElements($handle))->toBeTrue()
        ->and($nested->getEagerLoadedElements($handle))->toBeEmpty();

    $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
    expect($getter)->toBeNull();
})->with(['owner', 'primaryOwner']);

test('uploader eager-loading stays in sync with getUploader()', function () {
    $uploader = new User(['id' => 200]);
    $asset = new Asset(['id' => 2]);
    $plan = new EagerLoadPlan(handle: 'uploader');

    $asset->setEagerLoadedElements('uploader', [$uploader], $plan);

    expect($asset->hasEagerLoadedElements('uploader'))->toBeTrue();

    $eagerLoaded = $asset->getEagerLoadedElements('uploader');
    expect($eagerLoaded)->toBeInstanceOf(ElementCollection::class)
        ->and($eagerLoaded->all())->toBe([$uploader])
        ->and($asset->getUploader())->toBe($uploader);
});

test('uploader eager-loading with no uploader stays in sync with getUploader()', function () {
    $asset = new Asset(['id' => 2]);
    $plan = new EagerLoadPlan(handle: 'uploader');

    $asset->setEagerLoadedElements('uploader', [], $plan);

    expect($asset->hasEagerLoadedElements('uploader'))->toBeTrue()
        ->and($asset->getEagerLoadedElements('uploader'))->toBeEmpty()
        ->and($asset->getUploader())->toBeNull();
});

test('photo eager-loading stays in sync with getPhoto()', function () {
    $photo = new Asset(['id' => 300]);
    $user = new User(['id' => 3]);
    $plan = new EagerLoadPlan(handle: 'photo');

    $user->setEagerLoadedElements('photo', [$photo], $plan);

    expect($user->hasEagerLoadedElements('photo'))->toBeTrue();

    $eagerLoaded = $user->getEagerLoadedElements('photo');
    expect($eagerLoaded)->toBeInstanceOf(ElementCollection::class)
        ->and($eagerLoaded->all())->toBe([$photo])
        ->and($user->getPhoto())->toBe($photo);
});

test('photo eager-loading with no photo stays in sync with getPhoto()', function () {
    $user = new User(['id' => 3]);
    $plan = new EagerLoadPlan(handle: 'photo');

    $user->setEagerLoadedElements('photo', [], $plan);

    expect($user->hasEagerLoadedElements('photo'))->toBeTrue()
        ->and($user->getEagerLoadedElements('photo'))->toBeEmpty()
        ->and($user->getPhoto())->toBeNull();
});

test('author eager-loading stays in sync with getAuthor() and getAuthors()', function () {
    $author = new User(['id' => 400]);
    $entry = new Entry(['id' => 4]);
    $plan = new EagerLoadPlan(handle: 'author');

    $entry->setEagerLoadedElements('author', [$author], $plan);

    expect($entry->hasEagerLoadedElements('author'))->toBeTrue();

    $eagerLoaded = $entry->getEagerLoadedElements('author');
    expect($eagerLoaded)->toBeInstanceOf(ElementCollection::class)
        ->and($eagerLoaded->all())->toBe([$author])
        ->and($entry->getAuthor())->toBe($author)
        ->and($entry->getAuthors())->toBe([$author]);
});

test('authors eager-loading stays in sync with getAuthor() and getAuthors()', function () {
    $author1 = new User(['id' => 401]);
    $author2 = new User(['id' => 402]);
    $entry = new Entry(['id' => 4]);
    $plan = new EagerLoadPlan(handle: 'authors');

    $entry->setEagerLoadedElements('authors', [$author1, $author2], $plan);

    expect($entry->hasEagerLoadedElements('authors'))->toBeTrue();

    $eagerLoaded = $entry->getEagerLoadedElements('authors');
    expect($eagerLoaded)->toBeInstanceOf(ElementCollection::class)
        ->and($eagerLoaded->all())->toBe([$author1, $author2])
        ->and($entry->getAuthor())->toBe($author1)
        ->and($entry->getAuthors())->toBe([$author1, $author2]);
});

test('authors eager-loading with no authors stays in sync with getAuthor() and getAuthors()', function () {
    $entry = new Entry(['id' => 4]);
    $plan = new EagerLoadPlan(handle: 'authors');

    $entry->setEagerLoadedElements('authors', [], $plan);

    expect($entry->hasEagerLoadedElements('authors'))->toBeTrue()
        ->and($entry->getEagerLoadedElements('authors'))->toBeEmpty()
        ->and($entry->getAuthor())->toBeNull()
        ->and($entry->getAuthors())->toBe([]);
});
