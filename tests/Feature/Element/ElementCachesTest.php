<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Events\InvalidateElementCaches;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Facades\ElementCaches as ElementCachesFacade;
use Illuminate\Support\Facades\Event;

class TestElementCachesElement extends Element
{
    protected array $customCacheTags = [];

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public static function find(): ElementQuery
    {
        return new ElementQuery(static::class);
    }

    public function setCustomCacheTags(array $tags): void
    {
        $this->customCacheTags = $tags;
    }

    #[Override]
    protected function cacheTags(): array
    {
        return $this->customCacheTags;
    }
}

class TestNestedElementCachesElement extends TestElementCachesElement implements NestedElementInterface
{
    public ?ElementInterface $owner = null;

    public function getOwner(): ?ElementInterface
    {
        return $this->owner;
    }

    public function getPrimaryOwnerId(): ?int
    {
        return null;
    }

    public function setPrimaryOwnerId(?int $id): void {}

    public function getPrimaryOwner(): ?ElementInterface
    {
        return null;
    }

    public function setPrimaryOwner(?ElementInterface $owner): void {}

    public function getOwnerId(): ?int
    {
        return $this->owner?->id;
    }

    public function setOwnerId(?int $id): void {}

    public function setOwner(?ElementInterface $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwners(array $criteria = []): array
    {
        return $this->owner ? [$this->owner] : [];
    }

    public function getField(): ?ElementContainerFieldInterface
    {
        return null;
    }

    public function getSortOrder(): ?int
    {
        return null;
    }

    public function setSortOrder(?int $sortOrder): void {}

    public function setSaveOwnership(bool $saveOwnership): void {}
}

class TestExpirableElementCachesElement extends TestElementCachesElement implements ExpirableElementInterface
{
    public ?DateTime $expiryDate = null;

    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }
}

class TestRootOwnerExceptionElementCachesElement extends TestElementCachesElement
{
    #[Override]
    public function getRootOwner(): static
    {
        throw new RuntimeException('Root owner unavailable');
    }
}

beforeEach(function () {
    $this->elementCaches = app(ElementCaches::class);
});

it('is a singleton and is available via the facade', function () {
    expect(app(ElementCaches::class))->toBe(app(ElementCaches::class));

    expect($this->elementCaches)->toBe(ElementCachesFacade::getFacadeRoot());
});

it('collects cache info through the extracted service', function () {
    $this->elementCaches->startCollectingCacheInfo();

    expect($this->elementCaches->isCollectingCacheInfo())->toBeTrue();

    $element = new TestElementCachesElement;
    $element->id = 123;

    $this->elementCaches->collectCacheTags(['foo']);
    $this->elementCaches->collectCacheInfoForElement($element);

    [$dependency] = $this->elementCaches->stopCollectingCacheInfo();

    expect($dependency?->tags)->toBe([
        'foo',
        'element',
        'element::'.TestElementCachesElement::class,
        'element::123',
    ]);
});

it('returns null cache info when nothing was collected', function () {
    $this->elementCaches->startCollectingCacheInfo();

    expect($this->elementCaches->stopCollectingCacheInfo())->toBe([null, null]);
});

it('tracks the earliest collected cache expiry', function () {
    $this->elementCaches->startCollectingCacheInfo();

    $later = new DateTime('+10 minutes');
    $sooner = new DateTime('+5 minutes');

    $this->elementCaches->setCacheExpiryDate($later);
    $this->elementCaches->setCacheExpiryDate($sooner);
    $this->elementCaches->collectCacheTags(['foo']);

    [, $duration] = $this->elementCaches->stopCollectingCacheInfo();

    expect($duration)->toBeInt()
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(300);
});

it('collects expiry info from expirable elements', function () {
    $this->elementCaches->startCollectingCacheInfo();

    $element = new TestExpirableElementCachesElement;
    $element->id = 123;
    $element->expiryDate = new DateTime('+5 minutes');

    $this->elementCaches->collectCacheInfoForElement($element);

    [$dependency, $duration] = $this->elementCaches->stopCollectingCacheInfo();

    expect($dependency?->tags)->toBe([
        'element',
        'element::'.TestExpirableElementCachesElement::class,
        'element::123',
    ]);

    expect($duration)->toBeInt()
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(300);
});

it('throws when stopping cache collection without starting it', function () {
    $this->elementCaches->stopCollectingCacheInfo();
})->throws(RuntimeException::class, 'Element cache invalidation tags are not currently being collected.');

it('dispatches an event when invalidating all caches', function () {
    Event::fake([InvalidateElementCaches::class]);

    $tags = $this->elementCaches->invalidateAll();

    expect($tags)->toBe(['element']);

    Event::assertDispatched(fn (InvalidateElementCaches $event): bool => $event->tags === ['element']
        && $event->element === null);
});

it('dispatches an event when invalidating an element type', function () {
    Event::fake([InvalidateElementCaches::class]);

    $tags = $this->elementCaches->invalidateForElementType(Entry::class);

    expect($tags)->toBe(['element::'.Entry::class]);

    Event::assertDispatched(fn (InvalidateElementCaches $event): bool => $event->tags === ['element::'.Entry::class]
        && $event->element === null);
});

it('invalidates cached queries for an element type', function () {
    EntryModel::factory()->create();

    expect(entryQuery()->cache()->count())->toBe(1);

    EntryModel::factory()->create();

    expect(entryQuery()->cache()->count())->toBe(1);

    $this->elementCaches->invalidateForElementType(Entry::class);

    expect(entryQuery()->cache()->count())->toBe(2);
});

it('normalizes custom cache tags for an element', function () {
    Event::fake([InvalidateElementCaches::class]);

    $element = new TestElementCachesElement;
    $element->id = 123;
    $element->setCustomCacheTags(['custom', 'element::already-prefixed']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestElementCachesElement::class.'::*',
        'element::123',
        'element::'.TestElementCachesElement::class.'::custom',
        'element::already-prefixed',
    ]);

    Event::assertDispatched(fn (InvalidateElementCaches $event): bool => $event->tags === $tags
        && $event->element === $element);
});

it('includes owner tags for nested elements', function () {
    $owner = new TestElementCachesElement;
    $owner->id = 456;

    $element = new TestNestedElementCachesElement;
    $element->id = 123;
    $element->setOwner($owner);
    $element->setCustomCacheTags(['custom']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestNestedElementCachesElement::class.'::*',
        'element::123',
        'element::456',
        'element::'.TestNestedElementCachesElement::class.'::custom',
    ]);
});

it('handles nested elements without an owner', function () {
    $element = new TestNestedElementCachesElement;
    $element->id = 123;
    $element->setCustomCacheTags(['custom']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestNestedElementCachesElement::class.'::*',
        'element::123',
        'element::'.TestNestedElementCachesElement::class.'::custom',
    ]);
});

it('falls back to the owner when resolving the root owner fails', function () {
    $owner = new TestRootOwnerExceptionElementCachesElement;
    $owner->id = 456;
    $owner->draftId = 1;

    $element = new TestNestedElementCachesElement;
    $element->id = 123;
    $element->setOwner($owner);
    $element->setCustomCacheTags(['custom']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestNestedElementCachesElement::class.'::*',
        'element::123',
        'element::456',
        'element::'.TestNestedElementCachesElement::class.'::drafts',
    ]);
});

it('uses draft and revision invalidation tags instead of custom tags', function (callable $configureOwner, string $expectedTag) {
    $owner = new TestElementCachesElement;
    $owner->id = 456;
    $configureOwner($owner);

    $element = new TestNestedElementCachesElement;
    $element->id = 123;
    $element->setOwner($owner);
    $element->setCustomCacheTags(['custom']);

    $tags = $this->elementCaches->invalidateForElement($element);

    expect($tags)->toBe([
        'element::'.TestNestedElementCachesElement::class.'::*',
        'element::123',
        'element::456',
        $expectedTag,
    ]);
})->with([
    'draft root' => [
        function (TestElementCachesElement $owner): void {
            $owner->draftId = 1;
        },
        'element::'.TestNestedElementCachesElement::class.'::drafts',
    ],
    'revision root' => [
        function (TestElementCachesElement $owner): void {
            $owner->revisionId = 1;
        },
        'element::'.TestNestedElementCachesElement::class.'::revisions',
    ],
]);
