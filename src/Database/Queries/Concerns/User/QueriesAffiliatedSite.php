<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\User;

use CraftCms\Cms\Database\Queries\UserQuery;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use InvalidArgumentException;

/**
 * @internal
 */
trait QueriesAffiliatedSite
{
    /**
     * @var mixed The site(s) that resulting users must be affiliated with.
     *
     * @used-by affiliatedSiteId()
     */
    public mixed $affiliatedSiteId = null;

    protected function initQueriesAffiliatedSite(): void
    {
        if (! Sites::isMultiSite()) {
            return;
        }

        $this->beforeQuery(function (UserQuery $userQuery) {
            if (! $userQuery->affiliatedSiteId) {
                return;
            }

            $userQuery->subQuery->whereIn('users.affiliatedSiteId', Arr::wrap($this->affiliatedSiteId));
        });
    }

    /**
     * Narrows the query results based on the users’ affiliated sites.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'foo'` | affiliated with the site with a handle of `foo`.
     * | `['foo', 'bar']` | affiliated with a site with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not affiliated with a site with a handle of `foo` or `bar`.
     * | a [[Site]] object | affiliated with the site represented by the object.
     * | `'*'` | affiliated with any site.
     *
     * ---
     *
     * ```twig
     * {# Fetch users affiliated with the Foo site #}
     * {% set {elements-var} = {twig-method}
     *   .affiliatedSite('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users affiliated with the Foo site
     * ${elements-var} = {php-method}
     *     ->affiliatedSite('foo')
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $affiliatedSiteId
     */
    public function affiliatedSite(mixed $value): self
    {
        $this->affiliatedSiteId = match (true) {
            $value === null => null,
            $value === '*' => Sites::getAllSiteIds()->all(),
            $value instanceof Site => $value->id,
            is_string($value) => Sites::getSiteByHandle($value)->id
                ?? throw new InvalidArgumentException('Invalid site handle: '.$value),
            default => $this->parseSiteParam($value),
        };

        return $this;
    }

    private function parseSiteParam(array $value): array
    {
        if ($not = (strtolower((string) reset($value)) === 'not')) {
            array_shift($value);
        }

        $siteId = [];
        foreach (Sites::getAllSites() as $site) {
            if (in_array($site->handle, $value, true) === ! $not) {
                $siteId[] = $site->id;
            }
        }

        if (empty($siteId)) {
            throw new InvalidArgumentException('Invalid affiliatedSite param: ['.($not ? 'not, ' : '').implode(', ', $value).']');
        }

        return $siteId;
    }

    /**
     * Narrows the query results based on the users’ affiliated sites, per the site’s ID(s).
     *
     * Possible values include:
     *
     *  | Value | Fetches users…
     *  | - | -
     *  | `1` | affiliated with the site with an ID of `1`.
     *  | `[1, 2]` | affiliated with a site with an ID of `1` or `2`.
     *  | `['not', 1, 2]` | not affiliated with a site with an ID of `1` or `2`.
     *  | `'*'` | affiliated with any site.
     *
     *  ---
     *
     *  ```twig
     *  {# Fetch users affiliated with the site with an ID of 1 #}
     *  {% set {elements-var} = {twig-method}
     *    .affiliatedSiteId(1)
     *    .all() %}
     *  ```
     *
     *  ```php
     *  // Fetch users affiliated with the site with an ID of 1
     *  ${elements-var} = {php-method}
     *      ->affiliatedSiteId(1)
     *      ->all();
     *  ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $affiliatedSiteId
     */
    public function affiliatedSiteId(mixed $value): self
    {
        if (is_array($value) && strtolower((string) reset($value)) === 'not') {
            array_shift($value);

            $this->affiliatedSiteId = Sites::getAllSites()
                ->reject(fn (Site $site): bool => in_array($site->id, $value))
                ->pluck('id')
                ->all();

            return $this;
        }

        $this->affiliatedSiteId = $value;

        return $this;
    }
}
