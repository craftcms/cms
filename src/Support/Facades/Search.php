<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool indexElementAttributes(\craft\base\ElementInterface $element, ?array $fieldHandles = null)
 * @method static void queueIndexElement(\craft\base\ElementInterface $element, array $fieldHandles)
 * @method static void indexElementIfQueued(int $elementId, int $siteId, ?string $elementType = null)
 * @method static bool shouldCallSearchElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static array searchElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static \Illuminate\Database\Query\Builder|false createDbQuery(string|array|\CraftCms\Cms\Search\SearchQuery $searchQuery, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static \CraftCms\Cms\Search\SearchQuery normalizeSearchQuery(string|array|\CraftCms\Cms\Search\SearchQuery $searchQuery)
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
