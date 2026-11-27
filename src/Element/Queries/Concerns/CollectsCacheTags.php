<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Events\ElementQueryCacheTagsResolving;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use DateTimeInterface;

/**
 * @internal
 */
trait CollectsCacheTags
{
    /**
     * @var string[]|null
     *
     * @see getCacheTags()
     */
    private ?array $cacheTags = null;

    protected function initCollectsCacheTags(): void
    {
        $this->beforeQuery(static function (ElementQuery $elementQuery) {
            $elementQuery->cacheTags = null;
        });
    }

    /**
     * Registers this query’s cache tags with the caches being collected, along with the time its result will next
     * change without a save.
     *
     * It’s called when the query is prepared, so it covers every way of running it against the database. When
     * preparing the query aborts it, only the tags are registered, since the result can’t change until a save.
     *
     * @internal
     */
    protected function collectCacheInfo(bool $aborted = false): void
    {
        $collector = app(DependencyCollector::class);

        if (! $collector->isCollecting()) {
            return;
        }

        $collector->collectTags($this->getCacheTags());

        if (! $aborted && $collector->collectsStatusChanges() && ($date = $this->nextStatusChangeDate())) {
            $collector->setStatusChangeDate($date);
        }
    }

    /**
     * @return string[]
     */
    public function getCacheTags(): array
    {
        if (! is_null($this->cacheTags)) {
            return $this->cacheTags;
        }

        $modelClass = $this->elementType;

        $this->cacheTags = [
            'element',
            "element::{$modelClass}",
        ];

        // If (<= 100) specific IDs were requested, then use those
        if (is_numeric($this->id) ||
            (is_array($this->id) && count($this->id) <= 100 && Arr::isNumeric($this->id))
        ) {
            array_push($this->cacheTags, ...array_map(fn ($id) => "element::$id", Arr::wrap($this->id)));

            return $this->cacheTags;
        }

        $queryTags = $this->cacheTags();

        event($event = new ElementQueryCacheTagsResolving($this, $queryTags));

        $queryTags = $event->tags;

        if (! empty($queryTags)) {
            if ($this->drafts !== false) {
                $queryTags[] = 'drafts';
            }

            if ($this->revisions !== false) {
                $queryTags[] = 'revisions';
            }
        } else {
            $queryTags[] = '*';
        }

        foreach ($queryTags as $tag) {
            // tags can be provided fully-formed, or relative to the element type
            if (! str_starts_with((string) $tag, 'element::')) {
                $tag = sprintf('element::%s::%s', $this->elementType, $tag);
            }

            $this->cacheTags[] = $tag;
        }

        return $this->cacheTags;
    }

    /**
     * Registers this query’s cache tags, and its elements’ expiry dates, with the caches being collected when its
     * result comes from elements that were already loaded rather than from the database.
     *
     * Unlike [[collectCacheInfo()]], this doesn’t look up when other elements’ statuses change, such as when a pending
     * entry that would join the result goes live, since that would take the query the loaded elements saved.
     *
     * @internal
     *
     * @param  iterable<ElementInterface>  $elements
     */
    protected function collectCacheInfoForLoadedElements(iterable $elements = []): void
    {
        $collector = app(DependencyCollector::class);

        if (! $collector->isCollecting()) {
            return;
        }

        $collector->collectTags($this->getCacheTags());

        foreach ($elements as $element) {
            $collector->collectElementExpiry($element);
        }
    }

    /**
     * Returns when this query’s result will next change without any element being saved, such as when a pending entry
     * goes live, or `null` if it won’t.
     *
     * It’s called while caches are being collected, which expire at that time.
     */
    protected function nextStatusChangeDate(): ?DateTimeInterface
    {
        return null;
    }

    /**
     * Returns the earliest value of a date column after the given time among the elements this query could match with
     * the given statuses, on any of the sites it queries, for [[nextStatusChangeDate()]].
     *
     * The lookup skips the query’s events and the conditions their listeners add, its search, which it would otherwise
     * repeat, and its selects, orders, groups, and unions. That can only find an earlier date, or miss elements a union
     * adds.
     *
     * @param  string[]  $statuses
     */
    protected function nextDate(array $statuses, string $column, DateTimeInterface $after): ?DateTimeInterface
    {
        $query = (clone $this)->status($statuses)->search(null)->unique(false);
        $query->preparesSilently = true;

        // The structure join keeps MySQL from reading the date index in order, so leave it out unless a param or
        // condition of the query’s own could need it
        if (
            $query->query->wheres === [] &&
            ! array_any(self::STRUCTURE_PARAMS, fn (string $param) => ! in_array($query->$param, [null, false], true))
        ) {
            $query->withStructure(false);
        }

        // The prepared query keeps its own conditions grouped, so the date condition applies to all of them.
        // Ordering by the date lets the database stop at the first match.
        // Subqueries, such as relatedTo() ones, would still collect their own cache info.
        $date = app(DependencyCollector::class)->withoutCollecting(fn () => $query->toBase()
            ->cloneWithout(['columns', 'orders', 'limit', 'offset', 'groups', 'havings', 'unions', 'unionLimit', 'unionOffset', 'unionOrders', 'lock'])
            ->cloneWithoutBindings(['select', 'order', 'groupBy', 'having', 'union', 'unionOrder'])
            ->where($column, '>', Query::prepareDateForDb($after))
            ->orderBy($column)
            ->value($column));

        return DateTimeHelper::toDateTime($date) ?: null;
    }

    /**
     * Returns any cache invalidation tags that caches involving this element query should use as dependencies.
     *
     * Use the most specific tag(s) possible to reduce the likelihood of pointless cache clearing.
     *
     * When elements are created/updated/deleted, their [[ElementInterface::getCacheTags()]] method will be called,
     * and any caches that have those tags listed as dependencies will be invalidated.
     *
     * @return string[]
     */
    protected function cacheTags(): array
    {
        return [];
    }
}
