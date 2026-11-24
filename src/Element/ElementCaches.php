<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTimeInterface;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * @since 6.0.0
 */
#[Singleton]
class ElementCaches
{
    /**
     * The number of deferred tags that are invalidated early, so long-running operations such as bulk resaves
     * don't leave other requests reading stale caches until they finish.
     */
    private const int FLUSH_THRESHOLD = 500;

    private int $deferDepth = 0;

    /**
     * The database transaction level when the outermost deferral started.
     */
    private int $deferTransactionLevel = 0;

    /** @var array<string, true> */
    private array $deferredTags = [];

    private bool $registeredShutdownFlush = false;

    /**
     * Tags whose caches are bypassed until an after-commit invalidation runs, with the number of invalidations
     * waiting on each.
     *
     * @var array<string, int>
     */
    private array $bypassedTags = [];

    /**
     * Runs a callback, deferring the cache tags it invalidates until it returns, and then invalidating each tag once.
     *
     * Nested calls defer to the outermost one. Once 500 tags are pending, they're invalidated as soon as the
     * nested operation that added them finishes, unless a transaction opened since the outermost call is still
     * open. Pending tags are also invalidated on shutdown, in case the process exits or hits a fatal error before
     * the callback returns.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function deferInvalidations(callable $callback): mixed
    {
        if ($this->deferDepth === 0) {
            $this->deferTransactionLevel = DB::transactionLevel();
        }

        $this->deferDepth++;

        try {
            return $callback();
        } finally {
            $this->deferDepth--;

            if ($this->deferDepth === 0) {
                $this->flushDeferredInvalidations();
            } else {
                $this->flushDeferredInvalidationsOverThreshold();
            }
        }
    }

    /**
     * Returns whether any of the given cache tags are waiting to be invalidated by {@see deferInvalidations()}.
     *
     * Caches tagged with them are stale, so they shouldn't be read or written until the tags are invalidated.
     *
     * @param  string[]  $tags
     */
    public function hasPendingInvalidations(array $tags): bool
    {
        if ($this->deferredTags === [] && $this->bypassedTags === []) {
            return false;
        }

        return array_any($tags, fn ($tag) => isset($this->deferredTags[$tag]) || isset($this->bypassedTags[$tag]));
    }

    public function isCollectingCacheInfo(): bool
    {
        return $this->dependencyCollector()->isCollecting();
    }

    public function startCollectingCacheInfo(): void
    {
        $this->dependencyCollector()->begin(new TemplateCacheContext(
            cacheKey: '',
            global: false,
            resources: false,
        ));
    }

    /** @param list<string> $tags */
    public function collectCacheTags(array $tags): void
    {
        $this->dependencyCollector()->collectTags($tags);
    }

    public function setCacheExpiryDate(DateTimeInterface $expiryDate): void
    {
        $this->dependencyCollector()->setExpiryDate($expiryDate);
    }

    public function collectCacheInfoForElement(ElementInterface $element): void
    {
        $this->dependencyCollector()->collectElement($element);
    }

    /**
     * @return array{TagDependency|null, int|null}
     */
    public function stopCollectingCacheInfo(): array
    {
        return $this->dependencyCollector()->stop();
    }

    /** @return list<string> */
    public function invalidateAll(): array
    {
        $tags = $this->invalidateTags(['element']);

        event(new ElementCachesInvalidated($tags));

        return $tags;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return list<string>
     */
    public function invalidateForElementType(string $elementType): array
    {
        $tags = $this->invalidateTags(["element::$elementType"]);

        event(new ElementCachesInvalidated($tags));

        return $tags;
    }

    /**
     * Invalidates an element’s caches once the current transaction commits, and bypasses them until then, so code
     * that runs before the commit, such as event listeners, reads the element’s new state. If the transaction rolls
     * back, the caches are left alone.
     *
     * @internal
     */
    public function invalidateForElementAfterCommit(ElementInterface $element): void
    {
        $tags = $this->tagsForElement($element);

        foreach ($tags as $tag) {
            $this->bypassedTags[$tag] = ($this->bypassedTags[$tag] ?? 0) + 1;
        }

        $stopBypassing = function () use ($tags): void {
            foreach ($tags as $tag) {
                if (--$this->bypassedTags[$tag] === 0) {
                    unset($this->bypassedTags[$tag]);
                }
            }
        };

        DB::afterCommit(function () use ($element, $stopBypassing): void {
            try {
                $this->invalidateForElement($element);
            } finally {
                $stopBypassing();
            }
        });
        DB::afterRollBack($stopBypassing);
    }

    /**
     * @return list<string>
     */
    public function invalidateForElement(ElementInterface $element): array
    {
        $tags = $this->invalidateTags($this->tagsForElement($element));

        event(new ElementCachesInvalidated($tags, $element));

        return $tags;
    }

    /** @return list<string> */
    /**
     * @param  string[]  $tags
     * @return string[]
     */
    private function invalidateTags(array $tags): array
    {
        if ($this->deferDepth > 0) {
            foreach ($tags as $tag) {
                $this->deferredTags[$tag] = true;
            }

            if (! $this->registeredShutdownFlush) {
                register_shutdown_function($this->flushDeferredInvalidations(...));
                $this->registeredShutdownFlush = true;
            }

            $this->flushDeferredInvalidationsOverThreshold();

            return $tags;
        }

        TagDependency::invalidate($tags);

        return $tags;
    }

    private function flushDeferredInvalidations(): void
    {
        if ($this->deferredTags === []) {
            return;
        }

        $tags = array_keys($this->deferredTags);
        $this->deferredTags = [];
        TagDependency::invalidate($tags);
    }

    /**
     * Flushes pending tags once there are enough of them, but only between the outermost operation's
     * nested operations, after their transactions have committed. That way an element's tags are never
     * invalidated partway through its own save, or before another request can see its changes.
     */
    private function flushDeferredInvalidationsOverThreshold(): void
    {
        if (
            $this->deferDepth === 1 &&
            DB::transactionLevel() <= $this->deferTransactionLevel &&
            count($this->deferredTags) >= self::FLUSH_THRESHOLD
        ) {
            $this->flushDeferredInvalidations();
        }
    }

    /** @return list<string> */
    private function tagsForElement(ElementInterface $element): array
    {
        $elementClass = $element::class;
        $tags = [
            "element::{$elementClass}::*",
            "element::{$element->id}",
        ];

        $rootElement = $element;

        if ($element instanceof NestedElementInterface) {
            try {
                $owner = $element->getOwner();
            } catch (RuntimeException) {
                $owner = null;
            }

            if ($owner) {
                $tags[] = "element::{$owner->id}";

                try {
                    $rootElement = $owner->getRootOwner();
                } catch (Throwable) {
                    $rootElement = $owner;
                }
            }
        }

        if ($rootElement->getIsDraft()) {
            $tags[] = "element::{$elementClass}::drafts";

            return $tags;
        }

        if ($rootElement->getIsRevision()) {
            $tags[] = "element::{$elementClass}::revisions";

            return $tags;
        }

        foreach ($element->getCacheTags() as $tag) {
            if (! str_starts_with($tag, 'element::')) {
                $tag = "element::{$elementClass}::{$tag}";
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * We specifically want to resolve the DependencyCollector
     * from the container, as it is a scoped service, and
     * we don't want it locked inside this singleton.
     */
    private function dependencyCollector(): DependencyCollector
    {
        return app(DependencyCollector::class);
    }
}
