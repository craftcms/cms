<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Facades\ElementCaches as ElementCachesFacade;
use CraftCms\DependencyAwareCache\Events\TagsInvalidated;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;

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

    $later = now()->addMinutes(10);
    $sooner = now()->addMinutes(5);

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
    $element->expiryDate = now()->addMinutes(5);

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
    Event::fake([ElementCachesInvalidated::class]);

    $tags = $this->elementCaches->invalidateAll();

    expect($tags)->toBe(['element']);

    Event::assertDispatched(fn (ElementCachesInvalidated $event): bool => $event->tags === ['element']
        && $event->element === null);
});

it('dispatches an event when invalidating an element type', function () {
    Event::fake([ElementCachesInvalidated::class]);

    $tags = $this->elementCaches->invalidateForElementType(Entry::class);

    expect($tags)->toBe(['element::'.Entry::class]);

    Event::assertDispatched(fn (ElementCachesInvalidated $event): bool => $event->tags === ['element::'.Entry::class]
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
    Event::fake([ElementCachesInvalidated::class]);

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

    Event::assertDispatched(fn (ElementCachesInvalidated $event): bool => $event->tags === $tags
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

function elementCachesTestElement(int $id): TestElementCachesElement
{
    $element = new TestElementCachesElement;
    $element->id = $id;

    return $element;
}

/**
 * @return ArrayObject<int, list<string>>
 */
function recordElementCachesTagInvalidations(): ArrayObject
{
    $invalidations = new ArrayObject;
    Event::listen(TagsInvalidated::class, fn (TagsInvalidated $event) => $invalidations->append((array) $event->tags));

    return $invalidations;
}

it('invalidates tags right away outside a transaction, and once the transaction commits inside one', function () {
    $invalidations = recordElementCachesTagInvalidations();

    $this->elementCaches->invalidateForElement(elementCachesTestElement(123));

    expect($invalidations)->toHaveCount(1)
        ->and($invalidations[0])->toContain('element::123')
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeFalse();

    DB::transaction(function () use ($invalidations) {
        $this->elementCaches->invalidateForElement(elementCachesTestElement(456));

        expect($invalidations)->toHaveCount(1)
            ->and($this->elementCaches->hasPendingInvalidations(['element::456']))->toBeTrue();
    });

    expect($invalidations)->toHaveCount(2)
        ->and($invalidations[1])->toContain('element::456')
        ->and($this->elementCaches->hasPendingInvalidations(['element::456']))->toBeFalse();
});

it('invalidates deferred tags once the outermost transaction commits, even when a nested one rolls back', function () {
    $invalidations = recordElementCachesTagInvalidations();

    DB::transaction(function () use ($invalidations) {
        $this->elementCaches->deferInvalidations(function () {
            $this->elementCaches->deferInvalidations(fn () => $this->elementCaches->invalidateForElement(elementCachesTestElement(1)));
            $this->elementCaches->invalidateForElement(elementCachesTestElement(2));
        });

        try {
            DB::transaction(function () {
                $this->elementCaches->deferInvalidations(fn () => $this->elementCaches->invalidateForElement(elementCachesTestElement(3)));

                throw new RuntimeException('Rolled back');
            });
        } catch (RuntimeException) {
        }

        expect($invalidations)->toHaveCount(0);
    });

    expect($invalidations)->toHaveCount(1)
        ->and($invalidations[0])->toEqualCanonicalizing([
            'element::'.TestElementCachesElement::class.'::*',
            'element::1',
            'element::2',
            'element::3',
        ]);
});

it('keeps tags from a rolled-back transaction pending until the next flush', function () {
    $invalidations = recordElementCachesTagInvalidations();

    try {
        DB::transaction(function () {
            $this->elementCaches->invalidateForElement(elementCachesTestElement(123));

            throw new RuntimeException('Rolled back');
        });
    } catch (RuntimeException) {
    }

    expect($invalidations)->toHaveCount(0)
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeTrue();

    $this->elementCaches->invalidateForElement(elementCachesTestElement(456));

    expect($invalidations)->toHaveCount(1)
        ->and($invalidations[0])->toContain('element::123', 'element::456')
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeFalse();
});

it('invalidates pending tags when the request terminates if an earlier after-commit callback throws', function () {
    $invalidations = recordElementCachesTagInvalidations();

    DB::beginTransaction();
    DB::afterCommit(fn () => throw new RuntimeException('Callback failed'));
    $this->elementCaches->invalidateForElement(elementCachesTestElement(123));

    expect(fn () => DB::commit())->toThrow(RuntimeException::class, 'Callback failed')
        ->and($invalidations)->toHaveCount(0)
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeTrue();

    app()->terminate();

    expect($invalidations)->toHaveCount(1)
        ->and($invalidations[0])->toContain('element::123')
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeFalse();
});

it('invalidates leftover tags when a queued job finishes, but not a sync one', function () {
    $invalidations = recordElementCachesTagInvalidations();

    DB::beginTransaction();
    DB::afterCommit(fn () => throw new RuntimeException('Callback failed'));
    $this->elementCaches->invalidateForElement(elementCachesTestElement(123));

    expect(fn () => DB::commit())->toThrow(RuntimeException::class, 'Callback failed');

    event(new JobProcessed('sync', new SyncJob(app(), '{}', 'sync', 'default')));

    expect($invalidations)->toHaveCount(0)
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeTrue();

    event(new JobProcessed('database', Mockery::mock(Job::class, ['getQueue' => 'default', 'payload' => []])));

    expect($invalidations)->toHaveCount(1)
        ->and($invalidations[0])->toContain('element::123')
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeFalse();
});

it('reports a failed invalidation and keeps its tags pending for the next flush', function () {
    Exceptions::fake();
    $invalidations = recordElementCachesTagInvalidations();
    $store = new ArrayObject(['fails' => true]);
    Event::listen(TagsInvalidated::class, function () use ($store) {
        if ($store['fails']) {
            throw new RuntimeException('Cache store failed');
        }
    });

    $this->elementCaches->invalidateForElement(elementCachesTestElement(123));

    Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'Cache store failed');
    expect($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeTrue();

    $store['fails'] = false;
    $invalidations->exchangeArray([]);
    $this->elementCaches->invalidateForElement(elementCachesTestElement(456));

    expect($invalidations)->toHaveCount(1)
        ->and($invalidations[0])->toContain('element::123', 'element::456')
        ->and($this->elementCaches->hasPendingInvalidations(['element::123']))->toBeFalse();
});

it('drops a failed invalidation’s tags instead of retrying them when there are more than 1,000', function () {
    Exceptions::fake();
    Event::listen(TagsInvalidated::class, fn () => throw new RuntimeException('Cache store failed'));

    $this->elementCaches->deferInvalidations(function () {
        foreach (range(1, 1000) as $id) {
            $this->elementCaches->invalidateForElement(elementCachesTestElement($id));
        }
    });

    Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'Cache store failed');
    expect($this->elementCaches->hasPendingInvalidations(['element::1']))->toBeFalse();
});
