<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;

function entryElementForPostEditUrl(?Section $section): Entry
{
    $entry = new class extends Entry
    {
        public ?Section $mockSection = null;

        public function getSection(): ?Section
        {
            return $this->mockSection;
        }
    };

    $entry->mockSection = $section;

    return $entry;
}

describe('getPostEditUrl', function () {
    test('returns the section page when one is defined', function () {
        $section = new class extends Section
        {
            public ?string $mockPage = null;

            public function getPage(): ?string
            {
                return $this->mockPage;
            }
        };
        $section->mockPage = 'Marketing Pages';

        $entry = entryElementForPostEditUrl($section);

        expect($entry->getPostEditUrl())->toBe(Url::cpUrl('content/marketing-pages'));
    });

    test('falls back to the entries page when the section has no page', function () {
        $entry = entryElementForPostEditUrl(null);

        expect($entry->getPostEditUrl())->toBe(Url::cpUrl('content/entries'));
    });
});

/**
 * `owner`/`primaryOwner` are magic properties that resolve to `getOwner()`/`getPrimaryOwner()`
 * (via `NestedElement`, used by nested entries such as Matrix blocks). Since eager-loading those
 * handles bypasses the generic `Element::$_eagerLoadedElements` array (see
 * `NestedElement::setEagerLoadedElements()`), property access should always match the dedicated
 * getters, whether or not the owner has been eager-loaded.
 */
test('owner and primaryOwner property matches the dedicated getter', function (string $handle) {
    // No owner set at all
    $nested = new Entry(['id' => 1]);
    $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
    expect($getter)->toBeNull()->and($nested->$handle)->toBe($getter);

    // Owner set directly, not via eager-loading
    $owner = new Entry(['id' => 100]);
    $handle === 'owner' ? $nested->setOwner($owner) : $nested->setPrimaryOwner($owner);
    $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
    expect($getter)->toBe($owner)->and($nested->$handle)->toBe($getter);

    // Owner eager-loaded
    $nested2 = new Entry(['id' => 2]);
    $plan = new EagerLoadPlan(handle: $handle);
    $nested2->setEagerLoadedElements($handle, [$owner], $plan);
    $getter = $handle === 'owner' ? $nested2->getOwner() : $nested2->getPrimaryOwner();
    expect($getter)->toBe($owner)->and($nested2->$handle)->toBe($getter);

    // No owner eager-loaded (empty result)
    $nested3 = new Entry(['id' => 3]);
    $nested3->setEagerLoadedElements($handle, [], $plan);
    $getter = $handle === 'owner' ? $nested3->getOwner() : $nested3->getPrimaryOwner();
    expect($getter)->toBeNull()->and($nested3->$handle)->toBe($getter);
})->with(['owner', 'primaryOwner']);

/**
 * `author`/`authors` are magic properties that resolve to `getAuthor()`/`getAuthors()`. Since
 * eager-loading those handles bypasses the generic `Element::$_eagerLoadedElements` array (see
 * `Entry::setEagerLoadedElements()`), property access should always match the dedicated getters,
 * whether or not the authors have been eager-loaded.
 */
test('author and authors property matches the dedicated getters', function () {
    // No authors set at all
    $entry = new Entry(['id' => 4]);
    expect($entry->getAuthor())->toBeNull()
        ->and($entry->author)->toBe($entry->getAuthor())
        ->and($entry->authors)->toBe($entry->getAuthors());

    // Authors set directly, not via eager-loading
    $author1 = new User(['id' => 400]);
    $author2 = new User(['id' => 401]);
    $entry->setAuthors([$author1, $author2]);
    expect($entry->getAuthor())->toBe($author1)
        ->and($entry->author)->toBe($entry->getAuthor())
        ->and($entry->authors)->toBe($entry->getAuthors());

    // Authors eager-loaded
    $entry2 = new Entry(['id' => 5]);
    $plan = new EagerLoadPlan(handle: 'authors');
    $entry2->setEagerLoadedElements('authors', [$author1, $author2], $plan);
    expect($entry2->getAuthor())->toBe($author1)
        ->and($entry2->author)->toBe($entry2->getAuthor())
        ->and($entry2->authors)->toBe($entry2->getAuthors());

    // No authors eager-loaded (empty result)
    $entry3 = new Entry(['id' => 6]);
    $entry3->setEagerLoadedElements('authors', [], $plan);
    expect($entry3->getAuthor())->toBeNull()
        ->and($entry3->author)->toBe($entry3->getAuthor())
        ->and($entry3->authors)->toBe($entry3->getAuthors());
});
