<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Contracts\SchedulableElementInterface;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTimeInterface;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use RuntimeException;
use Throwable;

/**
 * @since 6.0.0
 */
#[Singleton]
readonly class ElementCaches
{
    /** The cache key that stores when {@see invalidateScheduled()} last ran, as a Unix timestamp. */
    public const string SCHEDULED_LAST_RUN_CACHE_KEY = 'craft:invalidate-scheduled-element-caches:last-run';

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
     * @return list<string>
     */
    public function invalidateForElement(ElementInterface $element): array
    {
        $tags = $this->invalidateTags($this->tagsForElement($element));

        event(new ElementCachesInvalidated($tags, $element));

        return $tags;
    }

    /**
     * Invalidates the caches of elements whose scheduled dates, such as entries’ post and expiry dates, passed since
     * the last run, and returns how many were invalidated.
     *
     * Each run covers the time since the previous one, so a late or missed run is caught up by the next. The first
     * run, or one after the cache is cleared, looks back one day.
     */
    public function invalidateScheduled(): int
    {
        $until = now();
        $lastRun = Cache::get(self::SCHEDULED_LAST_RUN_CACHE_KEY);
        $after = is_int($lastRun) ? Date::createFromTimestamp($lastRun) : $until->copy()->subDay();
        $count = 0;

        foreach (Elements::getAllElementTypes() as $elementType) {
            if (! is_subclass_of($elementType, SchedulableElementInterface::class)) {
                continue;
            }

            $elementType::findWithScheduledDatesBetween($after, $until)
                ->cursor()
                ->each(function (ElementInterface $element) use (&$count): void {
                    $this->invalidateForElement($element);
                    $count++;
                });
        }

        Cache::forever(self::SCHEDULED_LAST_RUN_CACHE_KEY, $until->getTimestamp());

        return $count;
    }

    /** @return list<string> */
    /**
     * @param  string[]  $tags
     * @return string[]
     */
    private function invalidateTags(array $tags): array
    {
        TagDependency::invalidate($tags);

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
