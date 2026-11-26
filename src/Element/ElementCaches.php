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
 * Collects the cache tags and expiry dates that caches depend on, and invalidates the caches tagged with them.
 *
 * Element tags, such as `element::5`, come from the elements a cache's content queries. Tags can also be custom,
 * such as `nav` added with `CacheInfo::addTags()`. Both kinds are invalidated the same way, firing
 * {@see ElementCachesInvalidated}.
 *
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

    private static bool $registeredShutdownFlush = false;

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
     * Returns whether any cache tags are waiting to be invalidated.
     *
     * Caches with dependencies whose tags can't be read, such as an `AnyDependency`, may be stale while this is true.
     */
    public function hasAnyPendingInvalidations(): bool
    {
        return $this->pendingTags !== [];
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

    /**
     * @param  bool  $withStatusChanges  Whether element queries should look up when their results will change without
     *                                   a save, such as when a pending entry goes live. Pass `false` when the collected
     *                                   info won’t be used to cache anything, to skip the lookups’ queries.
     *                                   The lookups still run when an enclosing cache collects them.
     */
    public function startCollectingCacheInfo(bool $withStatusChanges = true): void
    {
        $this->dependencyCollector()->begin(new TemplateCacheContext(
            cacheKey: '',
            global: false,
            resources: false,
        ));

        if (! $withStatusChanges) {
            $this->dependencyCollector()->skipStatusChanges();
        }
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

    /**
     * Invalidates the caches tagged with any of the given tags, such as tags added with `CacheInfo::addTags()`.
     *
     * This clears `{% cache %}` blocks and data caches that depend on them, and fires {@see ElementCachesInvalidated}
     * so cache integrations can purge the HTTP caches of the responses tagged with them.
     *
     * @param  list<string>  $tags
     * @return list<string>
     */
    public function invalidateTags(array $tags): array
    {
        $tags = array_values(array_unique(array_filter($tags, fn (string $tag) => $tag !== '')));

        if ($tags === []) {
            return [];
        }

        $tags = $this->queueInvalidations($tags);

        event(new ElementCachesInvalidated($tags));

        return $tags;
    }

    /**
     * Invalidates the caches tagged with any of the given tags like {@see invalidateTags()}, but right away, even
     * inside a database transaction or a {@see deferInvalidations()} call, and returns whether the cache store
     * cleared them. For places where someone asked for the caches to be cleared and should hear if they weren't.
     *
     * If the cache store fails, the error is reported and the tags stay pending for the next flush.
     *
     * @param  list<string>  $tags
     *
     * @internal
     */
    public function invalidateTagsNow(array $tags): bool
    {
        $tags = array_values(array_unique(array_filter($tags, fn (string $tag) => $tag !== '')));

        if ($tags === []) {
            return true;
        }

        $invalidated = true;

        try {
            TagDependency::invalidate($tags);
        } catch (Throwable $e) {
            report($e);
            $invalidated = false;

            foreach ($tags as $tag) {
                $this->pendingTags[$tag] = true;
            }
        }

        event(new ElementCachesInvalidated($tags));

        return $invalidated;
    }

    /** @return list<string> */
    public function invalidateAll(): array
    {
        return $this->invalidateTags(['element']);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return list<string>
     */
    public function invalidateForElementType(string $elementType): array
    {
        return $this->invalidateTags(["element::$elementType"]);
    }

    /**
     * Invalidates an element’s caches like {@see invalidateForElement()}, but fires {@see ElementCachesInvalidated}
     * once the open transaction commits rather than right away. If the transaction rolls back, it never fires.
     *
     * @internal
     */
    public function invalidateForElementAfterCommit(ElementInterface $element): void
    {
        $tags = $this->queueInvalidations($this->tagsForElement($element));

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
        $tags = $this->queueInvalidations($this->tagsForElement($element));

        event(new ElementCachesInvalidated($tags, $element));

        return $tags;
    }

    /**
     * @param  list<string>  $tags
     * @return list<string>
     */
    private function queueInvalidations(array $tags): array
    {
        foreach ($tags as $tag) {
            $this->pendingTags[$tag] = true;
        }

        if (! self::$registeredShutdownFlush) {
            // In case the process exits or hits a fatal error before the tags are invalidated. Registered once per
            // process, and resolves the service when it runs, so it doesn't keep Octane sandboxes' instances alive.
            register_shutdown_function(static function (): void {
                try {
                    app(self::class)->invalidatePendingTags();
                } catch (Throwable) {
                    // The app may already be gone
                }
            });
            self::$registeredShutdownFlush = true;
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
