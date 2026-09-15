<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Value\Value;

/**
 * @internal
 */
trait QueriesUniqueElements
{
    use QueriesSites;

    /**
     * @var bool Whether only elements with unique IDs should be returned by the query.
     *
     * @used-by unique()
     */
    public bool $unique = false;

    /**
     * @var array<int, int|string>|null Determines which site should be selected when querying multi-site elements.
     *
     * @used-by preferSites()
     */
    public ?array $preferSites = null;

    /** @param ElementQuery<*> $elementQuery */
    protected function applyUniqueParams(ElementQuery $elementQuery): void
    {
        if (! $elementQuery->unique) {
            return;
        }

        if (! Sites::isMultiSite(false, true)) {
            return;
        }

        $siteIds = $elementQuery->appliedSiteId ?? $elementQuery->siteId;

        if ($siteIds &&
            (! is_array($siteIds) || count($siteIds) === 1)
        ) {
            return;
        }

        $preferSites = collect($elementQuery->preferSites ?? [Sites::getCurrentSite()->id])
            ->map(fn (string|int $preferSite) => match (true) {
                is_numeric($preferSite) => $preferSite,
                ! is_null($site = Sites::getSiteByHandle($preferSite)) => $site->id,
                default => null,
            })
            ->filter();

        $cases = [];

        foreach ($preferSites as $index => $siteId) {
            $cases[] = new CaseRule(new Value($index), new Equal('elements_sites.siteId', new Value($siteId)));
        }

        $caseGroup = new CaseGroup($cases, new Value($preferSites->count()));

        $siteRows = $elementQuery->getQuery()
            ->cloneWithout(['orders', 'limit', 'offset'])
            ->cloneWithoutBindings(['order'])
            ->select([
                'elements_sites.id',
                'elements_sites.elementId',
                new Alias($caseGroup, 'sitePriority'),
            ]);

        $preferredSite = DB::query()
            ->fromSub($siteRows, 'uniqueSites')
            ->select('uniqueSites.id')
            ->whereColumn('uniqueSites.elementId', 'elements.id')
            ->orderBy('uniqueSites.sitePriority')
            ->orderBy('uniqueSites.id')
            ->limit(1);

        $elementQuery->where('elements_sites.id', '=', $preferredSite);
    }

    /**
     * Determines whether only elements with unique IDs should be returned by the query.
     *
     * This should be used when querying elements from multiple sites at the same time, if “duplicate” results is not
     * desired.
     *
     * ---
     *
     * ```twig
     * {# Fetch unique {elements} across all sites #}
     * {% set {elements-var} = {twig-method}
     *   .site('*')
     *   .unique()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch unique {elements} across all sites
     * ${elements-var} = {php-method}
     *     ->site('*')
     *     ->unique()
     *     ->all();
     * ```
     */
    public function unique(bool $value = true): static
    {
        $this->unique = $value;

        return $this;
    }

    /**
     * If [[unique()]] is set, this determines which site should be selected when querying multi-site elements.
     *
     * For example, if element “Foo” exists in Site A and Site B, and element “Bar” exists in Site B and Site C,
     * and this is set to `['c', 'b', 'a']`, then Foo will be returned for Site B, and Bar will be returned
     * for Site C.
     *
     * If this isn’t set, then preference goes to the current site.
     *
     * ---
     *
     * ```twig
     * {# Fetch unique {elements} from Site A, or Site B if they don’t exist in Site A #}
     * {% set {elements-var} = {twig-method}
     *   .site('*')
     *   .unique()
     *   .preferSites(['a', 'b'])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch unique {elements} from Site A, or Site B if they don’t exist in Site A
     * ${elements-var} = {php-method}
     *     ->site('*')
     *     ->unique()
     *     ->preferSites(['a', 'b'])
     *     ->all();
     * ```
     */
    /** @param array<int, int|string>|null $value */
    public function preferSites(?array $value = null): static
    {
        $this->preferSites = $value;

        return $this;
    }
}
