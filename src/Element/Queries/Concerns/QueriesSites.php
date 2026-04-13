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
        $this->beforeQuery(function (ElementQuery $elementQuery) {
            // Make sure the siteId param is set
            try {
                if (! $elementQuery->elementType::isLocalized()) {
                    // The criteria *must* be set to the primary site ID
                    $elementQuery->siteId = Sites::getPrimarySite()->id;
                } else {
                    $elementQuery->siteId = $this->normalizeSiteId($elementQuery);
                }
            } catch (SiteNotFoundException $e) {
                // Fail silently if Craft isn't installed yet or is in the middle of updating
                if (Cms::isInstalled() && ! Updates::isCraftUpdatePending()) {
                    throw $e;
                }

                throw new QueryAbortedException($e->getMessage(), 0, $e);
            }

            $elementQuery->appliedSiteId = $elementQuery->siteId;

            if (Sites::isMultiSite(false, true)) {
                $elementQuery->subQuery->whereIn('elements_sites.siteId', Arr::wrap($elementQuery->siteId));
            }
        });
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
            $this->siteId = Sites::getAllSiteIds();
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
        if (is_array($value) && strtolower((string) reset($value)) === 'not') {
            array_shift($value);

            $this->siteId = [];

            foreach (Sites::getAllSites() as $site) {
                if (! in_array($site->id, $value)) {
                    $this->siteId[] = $site->id;
                }
            }

            return $this;
        }

        $this->siteId = $value;

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
        if (is_string($value)) {
            $sites = Sites::getSitesByLanguage($value);

            if ($sites->isEmpty()) {
                throw new InvalidArgumentException("Invalid language: $value");
            }

            $this->siteId = $sites->pluck('id')->all();

            return $this;
        }

        if ($not = (strtolower((string) reset($value)) === 'not')) {
            array_shift($value);
        }

        $this->siteId = [];

        foreach (Sites::getAllSites() as $site) {
            if (in_array($site->language, $value, true) === ! $not) {
                $this->siteId[] = $site->id;
            }
        }

        if (empty($this->siteId)) {
            throw new InvalidArgumentException('Invalid language param: ['.($not ? 'not, ' : '').implode(', ',
                $value).']');
        }

        return $this;
    }

    /**
     * Normalizes the siteId param value.
     */
    private function normalizeSiteId(ElementQuery $query): mixed
    {
        if (is_null($query->siteId)) {
            // Default to the current site
            return Sites::getCurrentSite()->id;
        }

        if ($query->siteId === '*') {
            return Sites::getAllSiteIds()->all();
        }

        if ($query->siteId instanceof Collection) {
            $query->siteId = $query->siteId->all();
        }

        if (is_string($query->siteId)) {
            $query->siteId = str($query->siteId)
                ->explode(',')
                ->map(fn ($id) => trim($id))
                ->all();
        }

        if (is_numeric($query->siteId) || Arr::isNumeric($query->siteId)) {
            // Filter out any invalid site IDs
            $siteIds = Collection::make((array) $query->siteId)
                ->filter(fn ($siteId) => Sites::getSiteById($siteId, true) !== null)
                ->all();

            if (empty($siteIds)) {
                throw new QueryAbortedException;
            }

            return is_array($query->siteId) ? $siteIds : reset($siteIds);
        }

        return $query->siteId;
    }
}
