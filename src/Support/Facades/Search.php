<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool indexElementAttributes(\CraftCms\Cms\Element\Contracts\ElementInterface $element, array<array-key, mixed>|null $fieldHandles = null)
 * @method static void queueIndexElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element, string[] $fieldHandles)
 * @method static void indexElementIfQueued(int $elementId, int $siteId, string|null $elementType = null)
 * @method static bool shouldCallSearchElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static array<array-key, mixed> searchElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static \Illuminate\Database\Query\Builder|false createDbQuery(\CraftCms\Cms\Search\SearchQuery|array<array-key, mixed>|string $searchQuery, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static \CraftCms\Cms\Search\SearchQuery normalizeSearchQuery(\CraftCms\Cms\Search\SearchQuery|array<array-key, mixed>|string $searchQuery)
 * @method static void deleteOrphanedIndexes()
 * @method static void deleteOrphanedIndexJobs()
 *
 * @see \CraftCms\Cms\Search\Search
 */
class Search extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Search\Search::class;
    }
}
