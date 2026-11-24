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
     * The most tags a failed invalidation keeps pending for a retry. Larger batches are dropped, so a cache store
     * that keeps failing doesn't make every later flush retry a growing set.
     */
    private const int MAX_RETRIED_TAGS = 1000;

    private int $deferDepth = 0;

    /**
     * Tags waiting to be invalidated. Caches tagged with them are bypassed until then.
     *
     * @var array<string, true>
     */
    private array $pendingTags = [];

    private bool $registeredShutdownFlush = false;

    /**
     * Runs a callback, collecting the cache tags it invalidates, and invalidates each one once after it returns.
     *
     * Nested calls defer to the outermost one. Tags are never invalidated while a database transaction is open:
     * they stay pending until it commits. If it rolls back, they stay pending until the next flush or the end of
     * the request or queue job.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function deferInvalidations(callable $callback): mixed
    {
        $this->deferDepth++;

        try {
            return $callback();
        } finally {
            if (--$this->deferDepth === 0) {
                $this->flushPendingInvalidations();
            }
        }
    }

    /**
     * Returns whether any of the given cache tags are waiting to be invalidated.
     *
     * Caches tagged with them are stale, so they shouldn't be read or written until the tags are invalidated.
     *
     * @param  string[]  $tags
     */
    public function hasPendingInvalidations(array $tags): bool
    {
        return $this->pendingTags !== [] && array_any($tags, fn ($tag) => isset($this->pendingTags[$tag]));
    }

    /**
     * Invalidates the pending tags now, or once the open database transaction commits.
     *
     * @internal
     */
    public function flushPendingInvalidations(): void
    {
        if ($this->pendingTags !== []) {
            // Runs right away unless a transaction is open
            DB::afterCommit($this->invalidatePendingTags(...));
        }
    }

    /**
     * Invalidates the tags collected so far before an operation tells listeners that it has finished, unless a
     * wider {@see deferInvalidations()} call is still collecting. Like every flush, it waits for an open
     * transaction to commit.
     *
     * @internal
     */
    public function flushBeforeFinalEvent(): void
    {
        if ($this->deferDepth <= 1) {
            $this->flushPendingInvalidations();
        }
    }

    /**
     * Invalidates the pending tags now, whether or not a transaction is open.
     *
     * If the cache store fails, the error is reported and, unless there were more than 1,000 tags, they stay
     * pending for the next flush. This often runs after a commit, where throwing would tell the caller that a saved
     * change had failed.
     *
     * @internal
     */
    public function invalidatePendingTags(): void
    {
        if ($this->pendingTags === []) {
            return;
        }

        $tags = array_keys($this->pendingTags);
        $this->pendingTags = [];

        try {
            TagDependency::invalidate($tags);
        } catch (Throwable $e) {
            if (count($tags) <= self::MAX_RETRIED_TAGS) {
                foreach ($tags as $tag) {
                    $this->pendingTags[$tag] = true;
                }
            }

            report($e);
        }
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
     * Invalidates an element’s caches like {@see invalidateForElement()}, but fires {@see ElementCachesInvalidated}
     * once the open transaction commits rather than right away. If the transaction rolls back, it never fires.
     *
     * @internal
     */
    public function invalidateForElementAfterCommit(ElementInterface $element): void
    {
        $tags = $this->invalidateTags($this->tagsForElement($element));

        DB::afterCommit(function () use ($tags, $element): void {
            // Flush before the event, because a listener that throws makes Laravel skip the flush queued after this
            // callback. A deferInvalidations() call that's still collecting flushes once it returns.
            if ($this->deferDepth === 0) {
                $this->invalidatePendingTags();
            }

            event(new ElementCachesInvalidated($tags, $element));
        });
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
        foreach ($tags as $tag) {
            $this->pendingTags[$tag] = true;
        }

        if (! $this->registeredShutdownFlush) {
            // In case the process exits or hits a fatal error before the tags are invalidated
            register_shutdown_function(function (): void {
                try {
                    $this->invalidatePendingTags();
                } catch (Throwable) {
                    // The app may already be gone
                }
            });
            $this->registeredShutdownFlush = true;
        }

        if ($this->deferDepth === 0) {
            $this->flushPendingInvalidations();
        }

        return $tags;
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
