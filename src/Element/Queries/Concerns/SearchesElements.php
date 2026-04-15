<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Query\Builder;

/**
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
     * @see hydrate()
     */
    private ?array $searchResults = null;

    protected function initSearchesElements(): void
    {
        $this->beforeQuery(function (ElementQuery $elementQuery) {
            $this->applySearchParam($elementQuery);
        });
    }

    /**
     * Applies the 'search' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function applySearchParam(ElementQuery $elementQuery): void
    {
        $elementQuery->searchResults = null;

        if (! $elementQuery->search) {
            return;
        }

        $searchService = app(Search::class);

        $scoreOrder = Arr::first($elementQuery->query->orders ?? [], fn ($order) => $order['column'] === 'score');

        if ($scoreOrder || $searchService->shouldCallSearchElements($elementQuery)) {
            // Get the scored results up front
            $searchResults = $searchService->searchElements($elementQuery);

            if ($scoreOrder['direction'] === 'asc') {
                $searchResults = array_reverse($searchResults, true);
            }

            if (($elementQuery->query->orders[0]['column'] ?? null) === 'score') {
                // Only use the portion we're actually querying for
                if (is_int($elementQuery->query->getOffset()) && $elementQuery->query->getOffset() !== 0) {
                    $searchResults = array_slice($searchResults, $elementQuery->query->getOffset(), null, true);
                    $elementQuery->query->offset = null;
                    $elementQuery->query->unionOffset = null;
                }

                if (is_int($elementQuery->query->getLimit()) && $elementQuery->query->getLimit() !== 0) {
                    $searchResults = array_slice($searchResults, 0, $elementQuery->query->getLimit(), true);
                    $elementQuery->query->limit = null;
                    $elementQuery->query->unionLimit = null;
                }
            }

            if (empty($searchResults)) {
                throw new QueryAbortedException;
            }

            $elementQuery->searchResults = $searchResults;

            $elementIdsBySiteId = [];
            foreach (array_keys($searchResults) as $key) {
                [$elementId, $siteId] = explode('-', (string) $key, 2);
                $elementIdsBySiteId[$siteId][] = $elementId;
            }

            $elementQuery->where(function (Builder $query) use ($elementIdsBySiteId) {
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
        $searchQuery = $searchService->createDbQuery($elementQuery->search, $elementQuery);

        if ($searchQuery === false) {
            throw new QueryAbortedException;
        }

        $elementQuery->whereIn('elements.id', $searchQuery->pluck('elementId'));
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
