<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Asset;

use CraftCms\Cms\Element\Queries\AssetQuery;
use Illuminate\Database\Query\Builder;

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

            if ($this->hasAlt) {
                $assetQuery->where(function (Builder $query) {
                    $query->where('assets_sites.alt', '!=', '')
                        ->whereNotNull('assets_sites.alt');
                });
            } else {
                $assetQuery->where(function (Builder $query) {
                    $query->where('assets_sites.alt', '=', '')
                        ->orWhereNull('assets_sites.alt');
                });
            }
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
