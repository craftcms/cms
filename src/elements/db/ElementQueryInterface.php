<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use ArrayAccess;
use Countable;
use craft\base\ElementInterface;
use craft\models\Site;
use craft\search\SearchQuery;
use IteratorAggregate;
use yii\base\Arrayable;
use yii\db\Connection;
use yii\db\QueryInterface;

/**
 * ElementQueryInterface defines the common interface to be implemented by element query classes.
 * The default implementation of this interface is provided by [[ElementQuery]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ElementQueryInterface extends QueryInterface, ArrayAccess, Arrayable, Countable, IteratorAggregate
{
    /**
     * Causes the query results to be returned in reverse order.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in reverse #}
     * {% set {elements-var} = {twig-method}
     *     .inReverse()
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in reverse
     * ${elements-var} = {php-method}
     *     ->inReverse()
     *     ->all();
     * ```
     *
     * @param bool $value The property value
     * @return static self reference
     */
    public function inReverse(bool $value = true);

    /**
     * Causes the query to return matching {elements} as arrays of data, rather than [[{element-class}]] objects.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} as arrays #}
     * {% set {elements-var} = {twig-method}
     *     .asArray()
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} as arrays
     * ${elements-var} = {php-method}
     *     ->asArray()
     *     ->all();
     * ```
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     */
    public function asArray(bool $value = true);

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
     *     .id(1)
     *     .one() %}
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
     * @param int|int[]|false|null $value The property value
     * @return static self reference
     */
    public function id($value);

    /**
     * Narrows the query results based on the {elements}’ UIDs.
     *
     * ---
     *
     * ```twig
     * {# Fetch the {element} by its UID #}
     * {% set {element-var} = {twig-method}
     *     .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
     *     .one() %}
     * ```
     *
     * ```php
     * // Fetch the {element} by its UID
     * ${element-var} = {php-method}
     *     ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
     *     ->one();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     */
    public function uid($value);

    /**
     * Causes the query results to be returned in the order specified by [[id()]].
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in a specific order #}
     * {% set {elements-var} = {twig-method}
     *     .id([1, 2, 3, 4, 5])
     *     .fixedOrder()
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in a specific order
     * ${elements-var} = {php-method}
     *     ->id([1, 2, 3, 4, 5])
     *     ->fixedOrder()
     *     ->all();
     * ```
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     */
    public function fixedOrder(bool $value = true);

    /**
     * Narrows the query results based on the {elements}’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'enabled'`  _(default)_ | that are enabled.
     * | `'disabled'` | that are disabled.
     *
     * ---
     *
     * ```twig
     * {# Fetch disabled {elements} #}
     * {% set {elements-var} = {twig-method}
     *     .status('disabled')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch disabled {elements}
     * ${elements-var} = {php-method}
     *     ->status('disabled')
     *     ->all();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     */
    public function status($value);

    /**
     * Sets the [[$archived]] property.
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     */
    public function archived(bool $value = true);

    /**
     * Narrows the query results based on the {elements}’ creation dates.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'>= 2018-04-01'` | that were created on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were created before 2018-05-01
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created last month #}
     * {% set start = date('first day of last month')|atom %}
     * {% set end = date('first day of this month')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *     .dateCreated(['and', ">= #{start}", "< #{end}"])
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created last month
     * $start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
     * $end = new \DateTime('first day of this month')->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->dateCreated(['and', ">= {$start}", "< {$end}"])
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function dateCreated($value);

    /**
     * Narrows the query results based on the {elements}’ last-updated dates.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were updated before 2018-05-01
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} updated in the last week #}
     * {% set lastWeek = date('1 week ago')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *     .dateUpdated(">= #{lastWeek}")
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} updated in the last week
     * $lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->dateUpdated(">= {$lastWeek}")
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function dateUpdated($value);

    /**
     * Determines which site the {elements} should be queried in.
     *
     * The current site will be used by default.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | from the site with a handle of `foo`.
     * | a [[Site]] object | from the site represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} from the Foo site #}
     * {% set {elements-var} = {twig-method}
     *     .site('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} from the Foo site
     * ${elements-var} = {php-method}
     *     ->site('foo')
     *     ->all();
     * ```
     *
     * @param string|Site $value The property value
     * @return static self reference
     */
    public function site($value);

    /**
     * Determines which site the {elements} should be queried in, per the site’s ID.
     *
     * The current site will be used by default.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} from the site with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .siteId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} from the site with an ID of 1
     * ${elements-var} = {php-method}
     *     ->siteId(1)
     *     ->all();
     * ```
     *
     * @param int|null $value The property value
     * @return static self reference
     */
    public function siteId(int $value = null);

    /**
     * Narrows the query results based on whether the {elements} are enabled in the site they’re being queried in, per the [[site()]] parameter.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `true` _(default)_ | that are enabled in the site.
     * | `false` | whether they are enabled or not in the site.
     *
     * ---
     *
     * ```twig
     * {# Fetch all {elements}, including ones disabled for this site #}
     * {% set {elements-var} = {twig-method}
     *     .enabledForSite(false)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch all {elements}, including ones disabled for this site
     * ${elements-var} = {php-method}
     *     ->enabledForSite(false)
     *     ->all();
     * ```
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     */
    public function enabledForSite(bool $value = true);

    /**
     * Narrows the query results to only {elements} that are related to certain other elements.
     *
     * See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch all {elements} that are related to myCategory #}
     * {% set {elements-var} = {twig-method}
     *     .relatedTo(myCategory)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch all {elements} that are related to $myCategory
     * ${elements-var} = {php-method}
     *     ->relatedTo($myCategory)
     *     ->all();
     * ```
     *
     * @param int|array|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function relatedTo($value);

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
     * | `['*Foo*', '*Bar*'` | with a title that contains `Foo` or `Bar`.
     * | `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} with a title that contains "Foo" #}
     * {% set {elements-var} = {twig-method}
     *     .title('*Foo*')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} with a title that contains "Foo"
     * ${elements-var} = {php-method}
     *     ->title('*Foo*')
     *     ->all();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     */
    public function title($value);

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
     * | `['*foo*', '*bar*'` | with a slug that contains `foo` or `bar`.
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
     *     .slug(requestedSlug|literal)
     *     .one() %}
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
     * @param string|string[]|null $value The property value
     * @return static self reference
     */
    public function slug($value);

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
     * | `['*foo*', '*bar*'` | with a URI that contains `foo` or `bar`.
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
     *     .uri(requestedUri|literal)
     *     .one() %}
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
     * @param string|string[]|null $value The property value
     * @return static self reference
     */
    public function uri($value);

    /**
     * Narrows the query results to only {elements} that match a search query.
     *
     * See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Get the search query from the 'q' query string param #}
     * {% set searchQuery = craft.request.getQueryParam('q') %}
     *
     * {# Fetch all {elements} that match the search query #}
     * {% set {elements-var} = {twig-method}
     *     .search(searchQuery)
     *     .all() %}
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
     *
     * @param string|array|SearchQuery|null $value The property value
     * @return static self reference
     */
    public function search($value);

    /**
     * Narrows the query results based on a reference string.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     */
    public function ref($value);

    /**
     * Causes the query to return matching {elements} eager-loaded with related elements.
     *
     * See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} eager-loaded with the "Related" field’s relations #}
     * {% set {elements-var} = {twig-method}
     *     .with(['related'])
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} eager-loaded with the "Related" field’s relations
     * ${elements-var} = {php-method}
     *     ->with(['related'])
     *     ->all();
     * ```
     *
     * @param string|array|null $value The property value
     * @return self The query object itself
     */
    public function with($value);

    /**
     * Causes the query to return matching {elements} eager-loaded with related elements, in addition to the elements that were already specified by [[with()]]..
     *
     * @param string|array|null $value The property value to append
     * @return self The query object itself
     */
    public function andWith($value);

    /**
     * Explicitly determines whether the query should join in the structure data.
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     */
    public function withStructure(bool $value = true);

    /**
     * Determines which structure data should be joined into the query.
     *
     * @param int|null $value The property value
     * @return static self reference
     */
    public function structureId(int $value = null);

    /**
     * Narrows the query results based on the {elements}’ level within the structure.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | with a level of 1.
     * | `'not 1'` | not with a level of 1.
     * | `'>= 3'` | with a level greater than or equal to 3.
     * | `[1, 2]` | with a level of 1 or 2
     * | `['not', 1, 2]` | not with level of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} positioned at level 3 or above #}
     * {% set {elements-var} = {twig-method}
     *     .level('>= 3')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} positioned at level 3 or above
     * ${elements-var} = {php-method}
     *     ->level('>= 3')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function level($value = null);

    /**
     * Narrows the query results based on whether the {elements} have any descendants.
     *
     * (This has the opposite effect of calling [[leaves()]].)
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} that have descendants #}
     * {% set {elements-var} = {twig-method}
     *     .hasDescendants()
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} that have descendants
     * ${elements-var} = {php-method}
     *     ->hasDescendants()
     *     ->all();
     * ```
     *
     * @param bool $value The property value
     * @return static self reference
     */
    public function hasDescendants(bool $value = true);

    /**
     * Narrows the query results based on whether the {elements} are “leaves” ({elements} with no descendants).
     *
     * (This has the opposite effect of calling [[hasDescendants()]].)
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} that have no descendants #}
     * {% set {elements-var} = {twig-method}
     *     .leaves()
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} that have no descendants
     * ${elements-var} = {php-method}
     *     ->leaves()
     *     ->all();
     * ```
     *
     * @param bool $value The property value
     * @return static self reference
     */
    public function leaves(bool $value = true);

    /**
     * Narrows the query results to only {elements} that are ancestors of another {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | above the {element} with an ID of 1.
     * | a [[{element-class}]] object | above the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} above this one #}
     * {% set {elements-var} = {twig-method}
     *     .ancestorOf({myElement})
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} above this one
     * ${elements-var} = {php-method}
     *     ->ancestorOf(${myElement})
     *     ->all();
     * ```
     *
     * ---
     *
     * ::: tip
     * This can be combined with [[ancestorDist()]] if you want to limit how far away the ancestor {elements} can be.
     * :::
     *
     * @param int|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function ancestorOf($value);

    /**
     * Narrows the query results to only {elements} that are up to a certain distance away from the {element} specified by [[ancestorOf()]].
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} above this one #}
     * {% set {elements-var} = {twig-method}
     *     .ancestorOf({myElement})
     *     .ancestorDist(3)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} above this one
     * ${elements-var} = {php-method}
     *     ->ancestorOf(${myElement})
     *     ->ancestorDist(3)
     *     ->all();
     * ```
     *
     * @param int|null $value The property value
     * @return static self reference
     */
    public function ancestorDist(int $value = null);

    /**
     * Narrows the query results to only {elements} that are descendants of another {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | below the {element} with an ID of 1.
     * | a [[{element-class}]] object | below the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} below this one #}
     * {% set {elements-var} = {twig-method}
     *     .descendantOf({myElement})
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} below this one
     * ${elements-var} = {php-method}
     *     ->descendantOf(${myElement})
     *     ->all();
     * ```
     *
     * ---
     *
     * ::: tip
     * This can be combined with [[descendantDist()]] if you want to limit how far away the descendant {elements} can be.
     * :::
     *
     * @param int|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function descendantOf($value);

    /**
     * Narrows the query results to only {elements} that are up to a certain distance away from the {element} specified by [[descendantOf()]].
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} below this one #}
     * {% set {elements-var} = {twig-method}
     *     .descendantOf({myElement})
     *     .descendantDist(3)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} below this one
     * ${elements-var} = {php-method}
     *     ->descendantOf(${myElement})
     *     ->descendantDist(3)
     *     ->all();
     * ```
     *
     * @param int|null $value The property value
     * @return static self reference
     */
    public function descendantDist(int $value = null);

    /**
     * Narrows the query results to only {elements} that are siblings of another {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | beside the {element} with an ID of 1.
     * | a [[{element-class}]] object | beside the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} beside this one #}
     * {% set {elements-var} = {twig-method}
     *     .siblingOf({myElement})
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} beside this one
     * ${elements-var} = {php-method}
     *     ->siblingOf(${myElement})
     *     ->all();
     * ```
     *
     * @param int|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function siblingOf($value);

    /**
     * Narrows the query results to only the {element} that comes immediately before another {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches the {element}…
     * | - | -
     * | `1` | before the {element} with an ID of 1.
     * | a [[{element-class}]] object | before the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch the previous {element} #}
     * {% set {element-var} = {twig-method}
     *     .prevSiblingOf({myElement})
     *     .one() %}
     * ```
     *
     * ```php
     * // Fetch the previous {element}
     * ${element-var} = {php-method}
     *     ->prevSiblingOf(${myElement})
     *     ->one();
     * ```
     *
     * @param int|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function prevSiblingOf($value);

    /**
     * Narrows the query results to only the {element} that comes immediately after another {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches the {element}…
     * | - | -
     * | `1` | after the {element} with an ID of 1.
     * | a [[{element-class}]] object | after the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch the next {element} #}
     * {% set {element-var} = {twig-method}
     *     .nextSiblingOf({myElement})
     *     .one() %}
     * ```
     *
     * ```php
     * // Fetch the next {element}
     * ${element-var} = {php-method}
     *     ->nextSiblingOf(${myElement})
     *     ->one();
     * ```
     *
     * @param int|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function nextSiblingOf($value);

    /**
     * Narrows the query results to only {elements} that are positioned before another {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | before the {element} with an ID of 1.
     * | a [[{element-class}]] object | before the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} before this one #}
     * {% set {elements-var} = {twig-method}
     *     .positionedBefore({myElement})
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} before this one
     * ${elements-var} = {php-method}
     *     ->positionedBefore(${myElement})
     *     ->all();
     * ```
     *
     * @param int|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function positionedBefore($value);

    /**
     * Narrows the query results to only {elements} that are positioned after another {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | after the {element} with an ID of 1.
     * | a [[{element-class}]] object | after the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} after this one #}
     * {% set {elements-var} = {twig-method}
     *     .positionedAfter({myElement})
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} after this one
     * ${elements-var} = {php-method}
     *     ->positionedAfter(${myElement})
     *     ->all();
     * ```
     *
     * @param int|ElementInterface|null $value The property value
     * @return static self reference
     */
    public function positionedAfter($value);

    /**
     * Clears out the [[status()]] and [[enabledForSite()]] parameters.
     *
     * ---
     *
     * ```twig
     * {# Fetch all {elements}, regardless of status #}
     * {% set {elements-var} = {twig-method}
     *     .anyStatus()
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch all {elements}, regardless of status
     * ${elements-var} = {php-method}
     *     ->anyStatus()
     *     ->all();
     * ```
     *
     * @return static self reference
     */
    public function anyStatus();

    // Query preparation/execution
    // -------------------------------------------------------------------------

    /**
     * Executes the query and returns all results as an array.
     *
     * @param Connection|null $db The database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return ElementInterface[] The resulting elements.
     */
    public function all($db = null);

    /**
     * Executes the query and returns a single row of result.
     *
     * @param Connection $db The database connection used to execute the query.
     * If this parameter is not given, the `db` application
     * component will be used.
     * @return ElementInterface|array|null The resulting element. Null is returned if the query results in nothing.
     */
    public function one($db = null);

    /**
     * Executes the query and returns a single row of result at a given offset.
     *
     * @param int $n The offset of the row to return. If [[offset]] is set, $offset will be added to it.
     * @param Connection|null $db The database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return ElementInterface|array|null The element or row of the query result. Null is returned if the query
     * results in nothing.
     */
    public function nth(int $n, Connection $db = null);

    /**
     * Executes the query and returns the IDs of the resulting elements.
     *
     * @param Connection|null $db The database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return int[] The resulting element IDs. An empty array is returned if no elements are found.
     */
    public function ids($db = null): array;
}
