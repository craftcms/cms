<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Query\Builder;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait SearchesElements
{
    /**
     * @var mixed The search term to filter the resulting elements by.
     *
     * See [Searching](https://craftcms.com/docs/5.x/system/searching.html) for supported syntax options.
     *
     * @used-by ElementQuery::search()
     */
    public mixed $search = null;

    /**
     * @var array<string,int>|null
     *
     * @see applySearchParam()
     * @see applyOrderByParams()
     * @see populate()
     */
    private ?array $searchResults = null;

    protected function initSearchesElements(): void
    {
        $this->beforeQuery(function (ElementQuery $query) {
            $this->applySearchParam($query);
        });
    }

    /**
     * Applies the 'search' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function applySearchParam(ElementQuery $query): void
    {
        $this->searchResults = null;

        if (! $query->search) {
            return;
        }

        $searchService = \Craft::$app->getSearch();

        $scoreOrder = Arr::first($query->query->orders, fn ($order) => $order['column'] === 'score');

        if ($scoreOrder || $searchService->shouldCallSearchElements($this)) {
            // Get the scored results up front
            $searchResults = $searchService->searchElements($this);

            if ($scoreOrder['direction'] === 'asc') {
                $searchResults = array_reverse($searchResults, true);
            }

            if (($query->query->orders[0]['column'] ?? null) === 'score') {
                // Only use the portion we're actually querying for
                if (is_int($query->query->getOffset()) && $query->query->getOffset() !== 0) {
                    $searchResults = array_slice($searchResults, $query->query->getOffset(), null, true);
                    $query->subQuery->offset = null;
                    $query->subQuery->unionOffset = null;
                }
                if (is_int($query->query->getLimit()) && $query->query->getLimit() !== 0) {
                    $searchResults = array_slice($searchResults, 0, $query->query->getLimit(), true);
                    $query->subQuery->limit = null;
                    $query->subQuery->unionLimit = null;
                }
            }

            if (empty($searchResults)) {
                throw new QueryAbortedException;
            }

            $this->searchResults = $searchResults;

            $elementIdsBySiteId = [];
            foreach (array_keys($searchResults) as $key) {
                [$elementId, $siteId] = explode('-', (string) $key, 2);
                $elementIdsBySiteId[$siteId][] = $elementId;
            }

            $query->subQuery->where(function (Builder $query) use ($elementIdsBySiteId) {
                foreach ($elementIdsBySiteId as $siteId => $elementIds) {
                    $query->orWhere(function (Builder $query) use ($siteId, $elementIds) {
                        $query->where('elements_sites.siteId', $siteId)
                            ->whereIn('elements.id', $elementIds);
                    });
                }
            });

            return;
        }

        // Just filter the main query by the search query
        $searchQuery = $searchService->createDbQuery($this->search, $this);

        if ($searchQuery === false) {
            throw new QueryAbortedException;
        }

        $query->subQuery->whereIn('elements.id', $searchQuery->select('elementId')->all());
    }

    /**
     * Narrows the query results to only {elements} that match a search query.
     *
     * See [Searching](https://craftcms.com/docs/5.x/system/searching.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Get the search query from the 'q' query string param #}
     * {% set searchQuery = craft.app.request.getQueryParam('q') %}
     *
     * {# Fetch all {elements} that match the search query #}
     * {% set {elements-var} = {twig-method}
     *   .search(searchQuery)
     *   .all() %}
     * ```
     *
     * ```php
     * // Get the search query from the 'q' query string param
     * $searchQuery = \Craft::$app->request->getQueryParam('q');
     *
     * // Fetch all {elements} that match the search query
     * ${elements-var} = {php-method}
     *     ->search($searchQuery)
     *     ->all();
     * ```
     */
    public function search(mixed $value): static
    {
        $this->search = $value;

        return $this;
    }
}
