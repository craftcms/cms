<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Updates;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @internal
 */
trait QueriesSites
{
    /**
     * @var mixed The site ID(s) that the elements should be returned in, or `'*'` if elements
     *            should be returned in all supported sites.
     *
     * @used-by site()
     * @used-by siteId()
     */
    public mixed $siteId = null;

    private mixed $appliedSiteId = null;

    protected function initQueriesSites(): void
    {
        $this->beforeQuery(static function (ElementQuery $elementQuery) {
            // Make sure the siteId param is set
            try {
                if (! $elementQuery->elementType::isLocalized()) {
                    // The criteria *must* be set to the primary site ID
                    $elementQuery->siteId = Sites::getPrimarySite()->id;
                } else {
                    try {
                        $siteId = self::normalizeSiteId($elementQuery->siteId);
                    } catch (InvalidArgumentException $e) {
                        throw new QueryAbortedException(previous: $e);
                    }

                    // Default to the current site
                    $elementQuery->siteId = $siteId ?? Sites::getCurrentSite()->id;
                }
            } catch (SiteNotFoundException $e) {
                // Fail silently if Craft isn't installed yet or is in the middle of updating
                if (Cms::isInstalled() && ! Updates::isCraftUpdatePending()) {
                    throw $e;
                }

                throw new QueryAbortedException($e->getMessage(), 0, $e);
            }

            $elementQuery->appliedSiteId = $elementQuery->siteId;

            static::applySiteId($elementQuery, $elementQuery->siteId);
        });
    }

    public static function applySiteId(Builder $query, mixed $value): void
    {
        try {
            $siteId = self::normalizeSiteId($value);
        } catch (InvalidArgumentException) {
            $query->whereRaw('1 = 0');

            return;
        }

        if (is_null($siteId)) {
            return;
        }

        // Skip the (potentially costly) filter if there's only one site, unless the given value
        // didn't actually match any sites, in which case the query must still return no results.
        if ($siteId !== [] && ! Sites::isMultiSite(false, true)) {
            return;
        }

        $query->whereIn('elements_sites.siteId', Arr::wrap($siteId));
    }

    /**
     * Determines which site(s) the {elements} should be queried in.
     *
     * The current site will be used by default.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | from the site with a handle of `foo`.
     * | `['foo', 'bar']` | from a site with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a site with a handle of `foo` or `bar`.
     * | a [[Site]] object | from the site represented by the object.
     * | `'*'` | from any site.
     *
     * ::: tip
     * If multiple sites are specified, elements that belong to multiple sites will be returned multiple times. If you
     * only want unique elements to be returned, use [[unique()]] in conjunction with this.
     * :::
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} from the Foo site #}
     * {% set {elements-var} = {twig-method}
     *   .site('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} from the Foo site
     * ${elements-var} = {php-method}
     *     ->site('foo')
     *     ->all();
     * ```
     */
    public function site($value): static
    {
        if ($value === null) {
            $this->siteId = null;
        } elseif ($value === '*') {
            $this->siteId = Sites::getAllSiteIds()->all();
        } elseif ($value instanceof Site || $value instanceof SiteModel) {
            $this->siteId = $value->id;
        } elseif (is_string($value)) {
            $handles = str($value)->explode(',')->map(fn ($handle) => trim($handle))->all();

            $this->siteId = array_map(
                fn (string $handle) => Sites::getSiteByHandle($handle)->id ?? throw new InvalidArgumentException('Invalid site handle: '.$value),
                $handles,
            );
        } else {
            if ($not = (strtolower((string) reset($value)) === 'not')) {
                array_shift($value);
            }

            $this->siteId = [];

            foreach (Sites::getAllSites() as $site) {
                if (in_array($site->handle, $value, true) === ! $not) {
                    $this->siteId[] = $site->id;
                }
            }

            if (empty($this->siteId)) {
                throw new InvalidArgumentException('Invalid site param: ['.($not ? 'not, ' : '').implode(', ',
                    $value).']');
            }
        }

        return $this;
    }

    /**
     * Determines which site(s) the {elements} should be queried in, per the site’s ID.
     *
     * The current site will be used by default.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | from the site with an ID of `1`.
     * | `[1, 2]` | from a site with an ID of `1` or `2`.
     * | `['not', 1, 2]` | not in a site with an ID of `1` or `2`.
     * | `'*'` | from any site.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} from the site with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .siteId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} from the site with an ID of 1
     * ${elements-var} = {php-method}
     *     ->siteId(1)
     *     ->all();
     * ```
     */
    public function siteId($value): static
    {
        $this->siteId = self::normalizeSiteId($value);

        return $this;
    }

    /**
     * Determines which site(s) the {elements} should be queried in, based on their language.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'en'` | from sites with a language of `en`.
     * | `['en-GB', 'en-US']` | from sites with a language of `en-GB` or `en-US`.
     * | `['not', 'en-GB', 'en-US']` | not in sites with a language of `en-GB` or `en-US`.
     *
     * ::: tip
     * Elements that belong to multiple sites will be returned multiple times by default. If you
     * only want unique elements to be returned, use [[unique()]] in conjunction with this.
     * :::
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} from English sites #}
     * {% set {elements-var} = {twig-method}
     *   .language('en')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} from English sites
     * ${elements-var} = {php-method}
     *     ->language('en')
     *     ->all();
     * ```
     */
    public function language(mixed $value): static
    {
        $this->siteId = self::siteIdsFromLanguage($value);

        return $this;
    }

    /** @return int[] */
    public static function siteIdsFromLanguage(mixed $value): array
    {
        if (is_string($value)) {
            $sites = Sites::getSitesByLanguage($value);

            if ($sites->isEmpty()) {
                throw new InvalidArgumentException("Invalid language: $value");
            }

            return $sites->pluck('id')->all();
        }

        if ($not = (strtolower((string) reset($value)) === 'not')) {
            array_shift($value);
        }

        $siteIds = [];

        foreach (Sites::getAllSites() as $site) {
            if (in_array($site->language, $value, true) === ! $not) {
                $siteIds[] = $site->id;
            }
        }

        if (empty($siteIds)) {
            throw new InvalidArgumentException('Invalid language param: ['.($not ? 'not, ' : '').implode(', ', $value).']');
        }

        return $siteIds;
    }

    /**
     * Normalizes the siteId param value.
     *
     * @return int[]|int|null
     */
    private static function normalizeSiteId(mixed $siteId): array|int|null
    {
        if (is_null($siteId) || $siteId === '') {
            return null;
        }

        if ($siteId === '*') {
            return Sites::getAllSiteIds()->all();
        }

        if ($siteId instanceof Collection) {
            $siteId = $siteId->all();
        }

        if (is_string($siteId)) {
            $siteId = str($siteId)
                ->explode(',')
                ->map(fn ($id) => trim($id))
                ->all();
        }

        if (is_array($siteId) && empty($siteId)) {
            return [];
        }

        if (is_array($siteId) && strtolower((string) reset($siteId)) === 'not') {
            array_shift($siteId);

            $otherSiteIds = [];

            foreach (Sites::getAllSites() as $site) {
                if (! in_array($site->id, $siteId)) {
                    $otherSiteIds[] = $site->id;
                }
            }

            return $otherSiteIds;
        }

        if (! is_numeric($siteId) && ! Arr::isNumeric($siteId)) {
            throw new InvalidArgumentException('Invalid siteId value');
        }

        // Filter out any invalid site IDs
        $filteredSiteIds = Collection::make((array) $siteId)
            ->filter(fn ($siteId) => Sites::getSiteById($siteId, true) !== null)
            ->all();

        if (empty($filteredSiteIds)) {
            // None of the given site IDs are valid, so the query should return no results
            return [];
        }

        return is_array($siteId) ? $filteredSiteIds : reset($filteredSiteIds);
    }
}
