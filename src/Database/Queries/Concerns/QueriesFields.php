<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait QueriesFields
{
    /**
     * @var mixed The element ID(s). Prefix IDs with `'not '` to exclude them.
     *
     * @used-by id()
     */
    public mixed $id = null;

    /**
     * @var mixed The element UID(s). Prefix UIDs with `'not '` to exclude them.
     *
     * @used-by uid()
     */
    public mixed $uid = null;

    /**
     * @var mixed The element ID(s) in the `elements_sites` table. Prefix IDs with `'not '` to exclude them.
     *
     * @used-by siteSettingsId()
     */
    public mixed $siteSettingsId = null;

    /**
     * @var bool|null Whether to return trashed (soft-deleted) elements.
     *                If this is set to `null`, then both trashed and non-trashed elements will be returned.
     *
     * @used-by trashed()
     */
    public ?bool $trashed = false;

    /**
     * @var mixed When the resulting elements must have been created.
     *
     * @used-by dateCreated()
     */
    public mixed $dateCreated = null;

    /**
     * @var mixed When the resulting elements must have been last updated.
     *
     * @used-by dateUpdated()
     */
    public mixed $dateUpdated = null;

    /**
     * @var mixed The title that resulting elements must have.
     *
     * @used-by title()
     */
    public mixed $title = null;

    /**
     * @var mixed The slug that resulting elements must have.
     *
     * @used-by slug()
     */
    public mixed $slug = null;

    /**
     * @var mixed The URI that the resulting element must have.
     *
     * @used-by uri()
     */
    public mixed $uri = null;

    /**
     * @var string|null The bulk element operation key that the resulting elements were involved in.
     *
     * @used-by ElementQuery::inBulkOp()
     */
    public ?string $inBulkOp = null;

    protected function initializeQueriesFields(): void
    {
        $this->beforeQuery(function (ElementQuery $query) {
            if ($this->id) {
                Query::whereNumericParam($query->subQuery, 'elements.id', $this->id);
                // $query->subQuery->whereNumericParam('elements.id', $this->id);
            }

            if ($this->uid) {
                Query::whereParam($query->subQuery, 'elements.uid', $this->uid);
            }

            if ($this->siteSettingsId) {
                Query::whereNumericParam($query->subQuery, 'elements_sites.id', $this->siteSettingsId);
            }

            match ($this->trashed) {
                true => $query->subQuery->whereNotNull('elements.dateDeleted'),
                false => $query->subQuery->whereNull('elements.dateDeleted'),
                default => null,
            };

            if ($this->dateCreated) {
                Query::whereDateParam($query->subQuery, 'elements.dateCreated', $this->dateCreated);
            }

            if ($this->dateUpdated) {
                Query::whereDateParam($query->subQuery, 'elements.dateUpdated', $this->dateUpdated);
            }

            if (isset($this->title) && $this->title !== '' && $this->elementType::hasTitles()) {
                if (is_string($this->title)) {
                    $this->title = Query::escapeCommas($this->title);
                }

                Query::whereParam($query->subQuery, 'elements_sites.title', $this->title, caseInsensitive: true);
            }

            if ($this->slug) {
                Query::whereParam($query->subQuery, 'elements_sites.slug', $this->slug);
            }

            if ($this->uri) {
                Query::whereParam($query->subQuery, 'elements_sites.uri', $this->uri, caseInsensitive: true);
            }

            if ($this->inBulkOp) {
                $query->subQuery
                    ->join(new Alias(Table::ELEMENTS_BULKOPS, 'elements_bulkops'), 'elements_bulkops.elementId', 'elements.id')
                    ->where('elements_bulkops.key', $this->inBulkOp);
            }
        });
    }

    /**
     * Narrows the query results based on the {elements}’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | with an ID of 1.
     * | `'not 1'` | not with an ID of 1.
     * | `[1, 2]` | with an ID of 1 or 2.
     * | `['not', 1, 2]` | not with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch the {element} by its ID #}
     * {% set {element-var} = {twig-method}
     *   .id(1)
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch the {element} by its ID
     * ${element-var} = {php-method}
     *     ->id(1)
     *     ->one();
     * ```
     *
     * ---
     *
     * ::: tip
     * This can be combined with [[fixedOrder()]] if you want the results to be returned in a specific order.
     * :::
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function id(mixed $value): static
    {
        $this->id = $value;

        // Adjust query right away?
        // $this->subQuery->whereNumericParam('elements.id', $this->id);

        // Query::whereNumericParam($this->subQuery, 'elements.id', $this->id);

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ UIDs.
     *
     * ---
     *
     * ```twig
     * {# Fetch the {element} by its UID #}
     * {% set {element-var} = {twig-method}
     *   .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch the {element} by its UID
     * ${element-var} = {php-method}
     *     ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function uid(mixed $value): static
    {
        $this->uid = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ IDs in the `elements_sites` table.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | with an `elements_sites` ID of 1.
     * | `'not 1'` | not with an `elements_sites` ID of 1.
     * | `[1, 2]` | with an `elements_sites` ID of 1 or 2.
     * | `['not', 1, 2]` | not with an `elements_sites` ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch the {element} by its ID in the elements_sites table #}
     * {% set {element-var} = {twig-method}
     *   .siteSettingsId(1)
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch the {element} by its ID in the elements_sites table
     * ${element-var} = {php-method}
     *     ->siteSettingsId(1)
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function siteSettingsId(mixed $value): static
    {
        $this->siteSettingsId = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that have been soft-deleted.
     *
     * ---
     *
     * ```twig
     * {# Fetch trashed {elements} #}
     * {% set {elements-var} = {twig-method}
     *   .trashed()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch trashed {elements}
     * ${elements-var} = {element-class}::find()
     *     ->trashed()
     *     ->all();
     * ```
     *
     * @param  bool|null  $value  The property value (defaults to true)
     * @return static self reference
     */
    public function trashed(?bool $value = true): static
    {
        $this->trashed = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ creation dates.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'>= 2018-04-01'` | that were created on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were created before 2018-05-01.
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were created at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created last month #}
     * {% set start = date('first day of last month')|atom %}
     * {% set end = date('first day of this month')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .dateCreated(['and', ">= #{start}", "< #{end}"])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created last month
     * $start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
     * $end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->dateCreated(['and', ">= {$start}", "< {$end}"])
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function dateCreated(mixed $value): static
    {
        $this->dateCreated = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ last-updated dates.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were updated before 2018-05-01.
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were updated at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} updated in the last week #}
     * {% set lastWeek = date('1 week ago')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .dateUpdated(">= #{lastWeek}")
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} updated in the last week
     * $lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->dateUpdated(">= {$lastWeek}")
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function dateUpdated(mixed $value): static
    {
        $this->dateUpdated = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ titles.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'Foo'` | with a title of `Foo`.
     * | `'Foo*'` | with a title that begins with `Foo`.
     * | `'*Foo'` | with a title that ends with `Foo`.
     * | `'*Foo*'` | with a title that contains `Foo`.
     * | `'not *Foo*'` | with a title that doesn’t contain `Foo`.
     * | `['*Foo*', '*Bar*']` | with a title that contains `Foo` or `Bar`.
     * | `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} with a title that contains "Foo" #}
     * {% set {elements-var} = {twig-method}
     *   .title('*Foo*')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} with a title that contains "Foo"
     * ${elements-var} = {php-method}
     *     ->title('*Foo*')
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function title(mixed $value): static
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ slugs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | with a slug of `foo`.
     * | `'foo*'` | with a slug that begins with `foo`.
     * | `'*foo'` | with a slug that ends with `foo`.
     * | `'*foo*'` | with a slug that contains `foo`.
     * | `'not *foo*'` | with a slug that doesn’t contain `foo`.
     * | `['*foo*', '*bar*']` | with a slug that contains `foo` or `bar`.
     * | `['not', '*foo*', '*bar*']` | with a slug that doesn’t contain `foo` or `bar`.
     *
     * ---
     *
     * ```twig
     * {# Get the requested {element} slug from the URL #}
     * {% set requestedSlug = craft.app.request.getSegment(3) %}
     *
     * {# Fetch the {element} with that slug #}
     * {% set {element-var} = {twig-method}
     *   .slug(requestedSlug|literal)
     *   .one() %}
     * ```
     *
     * ```php
     * // Get the requested {element} slug from the URL
     * $requestedSlug = \Craft::$app->request->getSegment(3);
     *
     * // Fetch the {element} with that slug
     * ${element-var} = {php-method}
     *     ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function slug(mixed $value): static
    {
        $this->slug = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ URIs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | with a URI of `foo`.
     * | `'foo*'` | with a URI that begins with `foo`.
     * | `'*foo'` | with a URI that ends with `foo`.
     * | `'*foo*'` | with a URI that contains `foo`.
     * | `'not *foo*'` | with a URI that doesn’t contain `foo`.
     * | `['*foo*', '*bar*']` | with a URI that contains `foo` or `bar`.
     * | `['not', '*foo*', '*bar*']` | with a URI that doesn’t contain `foo` or `bar`.
     *
     * ---
     *
     * ```twig
     * {# Get the requested URI #}
     * {% set requestedUri = craft.app.request.getPathInfo() %}
     *
     * {# Fetch the {element} with that URI #}
     * {% set {element-var} = {twig-method}
     *   .uri(requestedUri|literal)
     *   .one() %}
     * ```
     *
     * ```php
     * // Get the requested URI
     * $requestedUri = \Craft::$app->request->getPathInfo();
     *
     * // Fetch the {element} with that URI
     * ${element-var} = {php-method}
     *     ->uri(\craft\helpers\Db::escapeParam($requestedUri))
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function uri(mixed $value): static
    {
        $this->uri = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that were involved in a bulk element operation.
     *
     * @param  string|null  $value  The property value
     * @return static self reference
     */
    public function inBulkOp(?string $value): static
    {
        $this->inBulkOp = $value;

        return $this;
    }
}
