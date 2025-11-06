<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use CraftCms\Cms\Db\Table;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Value\Value;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
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
     * @var array|null Determines which site should be selected when querying multi-site elements.
     *
     * @used-by preferSites()
     */
    public ?array $preferSites = null;

    protected function initializeQueriesUniqueElements(): void
    {
        if (
            ! $this->unique ||
            ! \Craft::$app->getIsMultiSite(false, true) ||
            (
                $this->siteId &&
                (! is_array($this->siteId) || count($this->siteId) === 1)
            )
        ) {
            return;
        }

        $sitesService = \Craft::$app->getSites();

        if (! $this->preferSites) {
            $preferSites = [$sitesService->getCurrentSite()->id];
        } else {
            $preferSites = [];
            foreach ($this->preferSites as $preferSite) {
                if (is_numeric($preferSite)) {
                    $preferSites[] = $preferSite;
                } elseif ($site = $sitesService->getSiteByHandle($preferSite)) {
                    $preferSites[] = $site->id;
                }
            }
        }

        $cases = [];

        foreach ($preferSites as $index => $siteId) {
            $cases[] = new CaseRule(new Value($index), new Equal('elements_sites.siteId', new Value($siteId)));
        }

        $caseGroup = new CaseGroup($cases, new Value(count($preferSites)));

        $subSelectSql = $this->subQuery->clone()
            ->select(['elements_sites.id'])
            ->whereColumn('subElements.id', 'tmpElements.id')
            ->orderBy($caseGroup)
            ->orderBy('elements_sites.id')
            ->offset(0)
            ->limit(1)
            ->toRawSql();

        // `elements` => `subElements`
        $qElements = DB::getTablePrefix().'Concerns'.Table::ELEMENTS;
        $qSubElements = DB::getTablePrefix().'.subElements';
        $qTmpElements = DB::getTablePrefix().'.tmpElements';
        $q = $qElements[0];
        $subSelectSql = str_replace("$qElements.", "$qSubElements.", $subSelectSql);
        $subSelectSql = str_replace("$q $qElements", "$q $qSubElements", $subSelectSql);
        $subSelectSql = str_replace($qTmpElements, $qElements, $subSelectSql);

        $this->subQuery->where(DB::raw("elements_sites.id = ($subSelectSql)"));
    }

    /**
     * {@inheritdoc}
     *
     * @uses $unique
     */
    public function unique(bool $value = true): static
    {
        $this->unique = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $preferSites
     */
    public function preferSites(?array $value = null): static
    {
        $this->preferSites = $value;

        return $this;
    }
}
