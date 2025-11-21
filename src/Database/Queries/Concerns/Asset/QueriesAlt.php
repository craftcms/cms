<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\Asset;

use CraftCms\Cms\Database\Queries\AssetQuery;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @internal
 */
trait QueriesAlt
{
    /**
     * @var bool|null Whether the query should filter assets depending on whether they have alternative text.
     *
     * @used-by hasAlt()
     */
    public ?bool $hasAlt = null;

    protected function initQueriesAlt(): void
    {
        $this->beforeQuery(function (AssetQuery $assetQuery) {
            if ($assetQuery->hasAlt === null) {
                return;
            }

            $hasAltCondition = function (Builder $query) {
                $query->where('assets_sites.alt', '!=', '')
                    ->orWhere(function (Builder $query) {
                        $query->whereNull('assets_sites.alt')
                            ->where('assets.alt', '!=', '')
                            ->whereNotNull('assets.alt');
                    });
            };

            $withoutAltCondition = function (Builder $query) {
                $query->where('assets_sites.alt', '=', '')
                    ->orWhere(function (Builder $query) {
                        $query->whereNull('assets_sites.alt')
                            ->where(function (Builder $query) {
                                $query->where('assets.alt', '=', '')
                                    ->orWhereNull('assets.alt');
                            });
                    });
            };

            $this->subQuery
                ->leftJoin(new Alias(Table::ASSETS_SITES, 'assets_sites'), function (JoinClause $join) {
                    $join->on('assets_sites.assetId', '=', 'assets.id')
                        ->whereColumn('assets_sites.siteId', '=', 'elements_sites.siteId');
                })
                ->where($this->hasAlt ? $hasAltCondition : $withoutAltCondition);
        });
    }

    /**
     * Narrows the query results based on whether the assets have alternative text.
     *
     * @uses $hasAlt
     */
    public function hasAlt(?bool $value = true): static
    {
        $this->hasAlt = $value;

        return $this;
    }
}
