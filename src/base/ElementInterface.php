<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\db\ElementQueryInterface;
use craft\models\FieldLayout;
use craft\models\Site;
use Twig\Markup;


/**
 * ElementInterface defines the common interface to be implemented by element classes.
 * A class implementing this interface should also use [[ElementTrait]] and [[ContentTrait]].
 *
 * @mixin ElementTrait
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface ElementInterface extends ComponentInterface
{
    /**
     * Returns the lowercase version of [[displayName()]].
     *
     * @return string
     * @since 3.3.17
     */
    public static function lowerDisplayName(): string;

    /**
     * Returns the plural version of [[displayName()]].
     *
     * @return string
     * @since 3.2.0
     */
    public static function pluralDisplayName(): string;

    /**
     * Returns the plural, lowercase version of [[displayName()]].
     *
     * @return string
     * @since 3.3.17
     */
    public static function pluralLowerDisplayName(): string;

    /**
     * Returns the handle that should be used to refer to this element type from reference tags.
     *
     * @return string|null The reference handle, or null if the element type doesn’t support reference tags
     */
    public static function refHandle();

    /**
     * Returns whether Craft should keep track of attribute and custom field changes made to this element type,
     * including when the last time they were changed, and who was logged-in at the time.
     *
     * @return bool Whether to track changes made to elements of this type.
     * @see getDirtyAttributes()
     * @see getDirtyFields()
     * @since 3.4.0
     */
    public static function trackChanges(): bool;

    /**
     * Returns whether elements of this type will be storing any data in the `content` table (titles or custom fields).
     *
     * @return bool Whether elements of this type will be storing any data in the `content` table.
     */
    public static function hasContent(): bool;

    /**
     * Returns whether elements of this type have traditional titles.
     *
     * @return bool Whether elements of this type have traditional titles.
     */
    public static function hasTitles(): bool;

    /**
     * Returns whether elements of this type can have their own slugs and URIs.
     *
     * Note that individual elements must also return a URI format from [[getUriFormat()]] if they are to actually get a URI.
     *
     * @return bool Whether elements of this type can have their own slugs and URIs.
     * @see getUriFormat()
     */
    public static function hasUris(): bool;

    /**
     * Returns whether elements of this type store content on a per-site basis.
     *
     * If this returns `true`, the element’s [[getSupportedSites()]] method will
     * be responsible for defining which sites its content should be stored in.
     *
     * @return bool Whether elements of this type store data on a per-site basis.
     */
    public static function isLocalized(): bool;

    /**
     * Returns whether elements of this type have statuses.
     *
     * If this returns `true`, the element index template will show a Status menu by default, and your elements will
     * get status indicator icons next to them.
     * Use [[statuses()]] to customize which statuses the elements might have.
     *
     * @return bool Whether elements of this type have statuses.
     * @see statuses()
     */
    public static function hasStatuses(): bool;

    /**
     * Creates an [[ElementQueryInterface]] instance for query purpose.
     *
     * The returned [[ElementQueryInterface]] instance can be further customized by calling
     * methods defined in [[ElementQueryInterface]] before `one()` or `all()` is called to return
     * populated [[ElementInterface]] instances. For example,
     *
     * ```php
     * // Find the entry whose ID is 5
     * $entry = Entry::find()->id(5)->one();
     * // Find all assets and order them by their filename:
     * $assets = Asset::find()
     *     ->orderBy('filename')
     *     ->all();
     * ```
     *
     * If you want to define custom criteria parameters for your elements, you can do so by overriding
     * this method and returning a custom query class. For example,
     *
     * ```php
     * class Product extends Element
     * {
     *     public static function find()
     *     {
     *         // use ProductQuery instead of the default ElementQuery
     *         return new ProductQuery(get_called_class());
     *     }
     * }
     * ```
     *
     * You can also set default criteria parameters on the ElementQuery if you don’t have a need for
     * a custom query class. For example,
     *
     * ```php
     * class Customer extends ActiveRecord
     * {
     *     public static function find()
     *     {
     *         return parent::find()->limit(50);
     *     }
     * }
     * ```
     *
     * @return ElementQueryInterface The newly created [[ElementQueryInterface]] instance.
     */
    public static function find(): ElementQueryInterface;

    /**
     * Returns a single element instance by a primary key or a set of element criteria parameters.
     *
     * The method accepts:
     *
     *  - an int: query by a single ID value and return the corresponding element (or null if not found).
     *  - an array of name-value pairs: query by a set of parameter values and return the first element
     *    matching all of them (or null if not found).
     *
     * Note that this method will automatically call the `one()` method and return an
     * [[ElementInterface|\craft\base\Element]] instance. For example,
     *
     * ```php
     * // find a single entry whose ID is 10
     * $entry = Entry::findOne(10);
     * // the above code is equivalent to:
     * $entry = Entry::find->id(10)->one();
     * // find the first user whose email ends in "example.com"
     * $user = User::findOne(['email' => '*example.com']);
     * // the above code is equivalent to:
     * $user = User::find()->email('*example.com')->one();
     * ```
     *
     * @param mixed $criteria The element ID or a set of element criteria parameters
     * @return static|null Element instance matching the condition, or null if nothing matches.
     */
    public static function findOne($criteria = null);

    /**
     * Returns a list of elements that match the specified ID(s) or a set of element criteria parameters.
     *
     * The method accepts:
     *
     *  - an int: query by a single ID value and return an array containing the corresponding element
     *    (or an empty array if not found).
     *  - an array of integers: query by a list of ID values and return the corresponding elements (or an
     *    empty array if none was found).
     *    Note that an empty array will result in an empty result as it will be interpreted as a search for
     *    primary keys and not an empty set of element criteria parameters.
     *  - an array of name-value pairs: query by a set of parameter values and return an array of elements
     *    matching all of them (or an empty array if none was found).
     *
     * Note that this method will automatically call the `all()` method and return an array of
     * [[ElementInterface|\craft\base\Element]] instances. For example,
     *
     * ```php
     * // find the entries whose ID is 10
     * $entries = Entry::findAll(10);
     * // the above code is equivalent to:
     * $entries = Entry::find()->id(10)->all();
     * // find the entries whose ID is 10, 11 or 12.
     * $entries = Entry::findAll([10, 11, 12]);
     * // the above code is equivalent to:
     * $entries = Entry::find()->id([10, 11, 12]])->all();
     * // find users whose email ends in "example.com"
     * $users = User::findAll(['email' => '*example.com']);
     * // the above code is equivalent to:
     * $users = User::find()->email('*example.com')->all();
     * ```
     *
     * @param mixed $criteria The element ID, an array of IDs, or a set of element criteria parameters
     * @return static[] an array of Element instances, or an empty array if nothing matches.
     */
    public static function findAll($criteria = null): array;

    /**
     * Returns all of the possible statuses that elements of this type may have.
     *
     * This method will be called when populating the Status menu on element indexes, for element types whose
     * [[hasStatuses()]] method returns `true`. It will also be called when [[\craft\elements\db\ElementQuery]] is querying for
     * elements, to ensure that its “status” parameter is set to a valid status.
     * It should return an array whose keys are the status values, and values are the human-facing status labels, or an array
     * with the following keys:
     * - **`label`** – The human-facing status label.
     * - **`color`** – The status color (green, orange, red, yellow, pink, purple, blue, turquoise, light, grey, black, or white)
     * You can customize the database query condition that should be applied for your custom statuses from
     * [[\craft\elements\db\ElementQuery::statusCondition()]].
     *
     * @return array
     * @see hasStatuses()
     */
    public static function statuses(): array;

    /**
     * Returns the source definitions that elements of this type may belong to.
     *
     * This defines what will show up in the source list on element indexes and element selector modals.
     *
     * Each item in the array should be set to an array that has the following keys:
     * - **`key`** – The source’s key. This is the string that will be passed into the $source argument of [[actions()]],
     *   [[indexHtml()]], and [[defaultTableAttributes()]].
     * - **`label`** – The human-facing label of the source.
     * - **`badgeCount`** – The badge count that should be displayed alongside the label. (Optional)
     * - **`sites`** – An array of site IDs that the source should be shown for, on multi-site element indexes. (Optional;
     *   by default the source will be shown for all sites.)
     * - **`criteria`** – An array of element criteria parameters that the source should use when the source is selected.
     *   (Optional)
     * - **`data`** – An array of `data-X` attributes that should be set on the source’s `<a>` tag in the source list’s,
     *   HTML, where each key is the name of the attribute (without the “data-” prefix), and each value is the value of
     *   the attribute. (Optional)
     * - **`defaultSort`** – A string identifying the sort attribute that should be selected by default, or an array where
     *   the first value identifies the sort attribute, and the second determines which direction to sort by. (Optional)
     * - **`hasThumbs`** – A bool that defines whether this source supports Thumbs View. (Use your element’s
     *   [[getThumbUrl()]] method to define your elements’ thumb URL.) (Optional)
     * - **`structureId`** – The ID of the Structure that contains the elements in this source. If set, Structure View
     *   will be available to this source. (Optional)
     * - **`newChildUrl`** – The URL that should be loaded when a user selects the “New child” menu option on an
     *   element in this source while it is in Structure View. (Optional)
     * - **`nested`** – An array of sources that are nested within this one. Each nested source can have the same keys
     *   as top-level sources.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::defineSources()]]
     * instead of this method.
     * :::
     *
     * @param string|null $context The context ('index' or 'modal').
     * @return array The sources.
     */
    public static function sources(string $context = null): array;

    /**
     * Returns all of the field layouts associated with elements from the given source.
     *
     * This is used to determine which custom fields should be included in the element index sort menu,
     * and other things.
     *
     * @param string $source The selected source’s key
     * @return FieldLayout[]
     * @since 3.5.0
     */
    public static function fieldLayouts(string $source): array;

    /**
     * Returns the available [element actions](https://craftcms.com/docs/3.x/extend/element-action-types.html) for a
     * given source.
     *
     * The actions can be represented by their fully qualified class name, a config array with the class name
     * set to a `type` key, or by an instantiated element action object.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::defineActions()]]
     * instead of this method.
     * :::
     *
     * @param string $source The selected source’s key.
     * @return array The available element actions.
     */
    public static function actions(string $source): array;

    /**
     * Returns the available export options for a given source.
     *
     * The exporters can be represented by their fully qualified class name, a config array with the class name
     * set to a `type` key, or by an instantiated element exporter object.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::defineExporters()]]
     * instead of this method.
     * :::
     *
     * @param string $source The selected source’s key.
     * @return array The available element exporters.
     * @since 3.4.0
     */
    public static function exporters(string $source): array;

    /**
     * Defines which element attributes should be searchable.
     *
     * This method should return an array of attribute names that can be accessed on your elements.
     * [[\craft\services\Search]] will call this method when it is indexing keywords for one of your elements,
     * and for each attribute it returns, it will fetch the corresponding property’s value on the element.
     * For example, if your elements have a “color” attribute which you want to be indexed, this method could return:
     *
     * ```php
     * return ['color'];
     * ```
     *
     * Not only will the “color” attribute’s values start getting indexed, but users will also be able to search
     * directly against that attribute’s values using this search syntax:
     *
     *     color:blue
     *
     * There is no need for this method to worry about the ‘title’ or ‘slug’ attributes, or custom field handles;
     * those are indexed automatically.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override
     * [[\craft\base\Element::defineSearchableAttributes()]] instead of this method.
     * :::
     *
     * @return string[] The element attributes that should be searchable
     */
    public static function searchableAttributes(): array;

    /**
     * Returns the element index HTML.
     *
     * @param ElementQueryInterface $elementQuery
     * @param int[]|null $disabledElementIds
     * @param array $viewState
     * @param string|null $sourceKey
     * @param string|null $context
     * @param bool $includeContainer
     * @param bool $showCheckboxes
     * @return string The element index HTML
     */
    public static function indexHtml(ElementQueryInterface $elementQuery, array $disabledElementIds = null, array $viewState, string $sourceKey = null, string $context = null, bool $includeContainer, bool $showCheckboxes): string;

    /**
     * Returns the sort options for the element type.
     *
     * This method should return an array, where each item is a sub-array with the following keys:
     *
     * - `label` – The sort option label
     * - `orderBy` – An array or comma-delimited string of columns to order the query by
     * - `attribute` _(optional)_ – The [[tableAttributes()|table attribute]] name that this option is associated
     *   with (required if `orderBy` is an array or more than one column name)
     *
     * ```php
     * return [
     *     [
     *         'label' => Craft::t('app', 'Attribute Label'),
     *         'orderBy' => 'columnName',
     *         'attribute' => 'attributeName'
     *     ],
     * ];
     * ```
     *
     * A shorthand syntax is also supported, if there is no corresponding table attribute, or the table attribute
     * has the exact same name as the column.
     *
     * ```php
     * return [
     *     'columnName' => Craft::t('app', 'Attribute Label'),
     * ];
     * ```
     *
     * Note that this method will only get called once for the entire index; not each time that a new source is
     * selected.
     *
     * @return array The attributes that elements can be sorted by
     */
    public static function sortOptions(): array;

    /**
     * Defines all of the available columns that can be shown in table views.
     *
     * This method should return an array whose keys map to attribute names and database columns that can be sorted
     * against when querying for elements, and whose values make up the table’s column headers.
     * The *first* item that this array returns will just identify the database column name, and the table column’s
     * header, but will **not** have any effect on what shows up in the table’s body. That’s because the first column is
     * reserved for displaying whatever your element’s __toString() method returns.
     * All other items besides the first one will also define which element attribute should be shown within the data
     * cells. (The actual HTML to be shown can be customized with [[getTableAttributeHtml()]].)
     *
     * @return array The table attributes.
     */
    public static function tableAttributes(): array;

    /**
     * Returns the list of table attribute keys that should be shown by default.
     *
     * This method should return an array where each element in the array maps to one of the keys of the array returned
     * by [[tableAttributes()]].
     *
     * @param string $source The selected source’s key
     * @return string[] The table attribute keys
     */
    public static function defaultTableAttributes(string $source): array;

    /**
     * Returns an array that maps source-to-target element IDs based on the given sub-property handle.
     *
     * This method aids in the eager-loading of elements when performing an element query. The returned array should
     * contain the following keys:
     * - `elementType` – the fully qualified class name of the element type that should be eager-loaded
     * - `map` – an array of element ID mappings, where each element is a sub-array with `source` and `target` keys.
     * - `criteria` *(optional)* – Any criteria parameters that should be applied to the element query when fetching the eager-loaded elements.
     *
     * ```php
     * use craft\db\Query;
     * use craft\helpers\ArrayHelper;
     *
     * public static function eagerLoadingMap(array $sourceElements, string $handle) {
     *     switch ($handle) {
     *         case 'author':
     *             $bookIds = ArrayHelper::getColumn($sourceElements, 'id');
     *             $map = (new Query)
     *                 ->select(['source' => 'id', 'target' => 'authorId'])
     *                 ->from('{{%books}}')
     *                 ->where(['id' => $bookIds)
     *                 ->all();
     *             return [
     *                 'elementType' => \my\plugin\Author::class,
     *                 'map' => $map,
     *             ];
     *         case 'bookClubs':
     *             $bookIds = ArrayHelper::getColumn($sourceElements, 'id');
     *             $map = (new Query)
     *                 ->select(['source' => 'bookId', 'target' => 'clubId'])
     *                 ->from('{{%bookclub_books}}')
     *                 ->where(['bookId' => $bookIds)
     *                 ->all();
     *             return [
     *                 'elementType' => \my\plugin\BookClub::class,
     *                 'map' => $map,
     *             ];
     *         default:
     *             return parent::eagerLoadMap($sourceElements, $handle);
     *     }
     * }
     * ```
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @param string $handle The property handle used to identify which target elements should be included in the map
     * @return array|false|null The eager-loading element ID mappings, false if no mappings exist, or null if the result
     * should be ignored
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle);

    /**
     * Returns the GraphQL type name by an element's context.
     *
     * @param mixed $context The element's context, such as a Volume, Entry Type or Matrix Block Type.
     * @return string
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string;

    /**
     * Returns the GraphQL mutation name by an element's context.
     *
     * @param mixed $context The element's context, such as a volume, entry type, or Matrix block type.
     * @return string
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string;

    /**
     * Returns the GraphQL scopes required by element's context.
     *
     * @param mixed $context The element's context, such as a Volume, Entry Type or Matrix Block Type.
     * @return array
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array;

    /**
     * Returns the element’s ID.
     *
     * @return int|null
     * @internal This method is required by [[\yii\web\IdentityInterface]], but might as well
     * go here rather than only in [[\craft\elements\User]].
     */
    public function getId();

    /**
     * Returns whether this is a draft.
     *
     * @return bool
     * @since 3.2.0
     */
    public function getIsDraft(): bool;

    /**
     * Returns whether this is a revision.
     *
     * @return bool
     * @since 3.2.0
     */
    public function getIsRevision(): bool;

    /**
     * Returns the element’s ID, or if it’s a draft/revision, its source element’s ID.
     *
     * @return int|null
     * @since 3.2.0
     */
    public function getSourceId();

    /**
     * Returns the element’s UUID, or if it’s a draft/revision, its source element’s UUID.
     *
     * @return string
     * @since 3.2.0
     */
    public function getSourceUid(): string;

    /**
     * Returns whether the element is an unsaved draft.
     *
     * @return bool
     * @since 3.2.0
     */
    public function getIsUnsavedDraft(): bool;

    /**
     * Returns the field layout used by this element.
     *
     * @return FieldLayout|null
     */
    public function getFieldLayout();

    /**
     * Returns the site the element is associated with.
     *
     * @return Site
     */
    public function getSite(): Site;

    /**
     * Returns the language of the element.
     *
     * @return string
     * @since 3.5.0
     */
    public function getLanguage(): string;

    /**
     * Returns the sites this element is associated with.
     *
     * The function can either return an array of site IDs, or an array of sub-arrays,
     * each with the following keys:
     *
     * - `siteId` (integer) - The site ID
     * - `propagate` (boolean) – Whether the element should be propagated to this site on save (`true` by default)
     * - `enabledByDefault` (boolean) – Whether the element should be enabled in this site by default
     *   (`true` by default)
     *
     * @return int[]|array
     */
    public function getSupportedSites(): array;

    /**
     * Returns the URI format used to generate this element’s URI.
     *
     * Note that element types that can have URIs must return `true` from [[hasUris()]].
     *
     * @return string|null
     * @see hasUris()
     * @see getRoute()
     */
    public function getUriFormat();

    /**
     * Returns the search keywords for a given search attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function getSearchKeywords(string $attribute): string;

    /**
     * Returns the route that should be used when the element’s URI is requested.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::route()]]
     * instead of this method.
     * :::
     *
     * @return mixed The route that the request should use, or null if no special action should be taken
     */
    public function getRoute();

    /**
     * Returns whether this element represents the site homepage.
     *
     * @return bool
     * @since 3.3.6
     */
    public function getIsHomepage(): bool;

    /**
     * Returns the element’s full URL.
     *
     * @return string|null
     */
    public function getUrl();

    /**
     * Returns an anchor pre-filled with this element’s URL and title.
     *
     * @return Markup|null
     */
    public function getLink();

    /**
     * Returns what the element should be called within the control panel.
     *
     * @return string
     * @since 3.2.0
     */
    public function getUiLabel(): string;

    /**
     * Returns the reference string to this element.
     *
     * @return string|null
     */
    public function getRef();

    /**
     * Returns whether the current user can edit the element.
     *
     * @return bool
     */
    public function getIsEditable(): bool;

    /**
     * Returns the element’s edit URL in the control panel.
     *
     * @return string|null
     */
    public function getCpEditUrl();

    /**
     * Returns the additional locations that should be available for previewing the element, besides its primary [[getUrl()|URL]].
     *
     * Each target should be represented by a sub-array with the following keys:
     *
     * - `label` – What the preview target will be called in the control panel.
     * - `url` – The URL that the preview target should open.
     * - `refresh` – Whether preview frames should be automatically refreshed when content changes (`true` by default).
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::previewTargets()]]
     * instead of this method.
     * :::
     *
     * @return array
     * @since 3.2.0
     */
    public function getPreviewTargets(): array;

    /**
     * Returns the URL to the element’s thumbnail, if there is one.
     *
     * @param int $size The maximum width and height the thumbnail should have.
     * @return string|null
     */
    public function getThumbUrl(int $size);

    /**
     * Returns whether the element’s thumbnail should have a checkered background.
     *
     * @return bool
     * @since 3.5.5
     */
    public function getHasCheckeredThumb(): bool;

    /**
     * Returns whether the element’s thumbnail should be rounded.
     *
     * @return bool
     * @since 3.5.5
     */
    public function getHasRoundedThumb(): bool;

    /**
     * Returns whether the element is enabled for the current site.
     *
     * This can also be set to an array of site ID/site-enabled mappings.
     *
     * @param int|null $siteId The ID of the site to return for. If `null`, the current site status will be returned.
     * @return bool|null Whether the element is enabled for the given site. `null` will be returned if a `$siteId` was
     * passed, but that site’s status wasn’t provided via [[setEnabledForSite()]].
     * @since 3.4.0
     */
    public function getEnabledForSite(int $siteId = null);

    /**
     * Sets whether the element is enabled for the current site.
     *
     * This can also be set to an array of site ID/site-enabled mappings.
     *
     * @param bool|bool[] $enabledForSite
     * @since 3.4.0
     */
    public function setEnabledForSite($enabledForSite);

    /**
     * Returns the element’s status.
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Returns the same element in other locales.
     *
     * @return ElementQueryInterface[]|ElementInterface[]
     */
    public function getLocalized();

    /**
     * Returns the next element relative to this one, from a given set of criteria.
     *
     * @param mixed $criteria
     * @return ElementInterface|null
     */
    public function getNext($criteria = false);

    /**
     * Returns the previous element relative to this one, from a given set of criteria.
     *
     * @param mixed $criteria
     * @return ElementInterface|null
     */
    public function getPrev($criteria = false);

    /**
     * Sets the default next element.
     *
     * @param ElementInterface|false $element
     */
    public function setNext($element);

    /**
     * Sets the default previous element.
     *
     * @param ElementInterface|false $element
     * return void
     */
    public function setPrev($element);

    /**
     * Returns the element’s parent.
     *
     * @return ElementInterface|null
     */
    public function getParent();

    /**
     * Sets the element’s parent.
     *
     * @param ElementInterface|null $parent
     */
    public function setParent(ElementInterface $parent = null);

    /**
     * Returns the element’s ancestors.
     *
     * @param int|null $dist
     * @return ElementQueryInterface|ElementInterface[]
     */
    public function getAncestors(int $dist = null);

    /**
     * Returns the element’s descendants.
     *
     * @param int|null $dist
     * @return ElementQueryInterface|ElementInterface[]
     */
    public function getDescendants(int $dist = null);

    /**
     * Returns the element’s children.
     *
     * @return ElementQueryInterface|ElementInterface[]
     */
    public function getChildren();

    /**
     * Returns all of the element’s siblings.
     *
     * @return ElementQueryInterface|ElementInterface[]
     */
    public function getSiblings();

    /**
     * Returns the element’s previous sibling.
     *
     * @return ElementInterface|null
     */
    public function getPrevSibling();

    /**
     * Returns the element’s next sibling.
     *
     * @return ElementInterface|null
     */
    public function getNextSibling();

    /**
     * Returns whether the element has descendants.
     *
     * @return bool
     */
    public function getHasDescendants(): bool;

    /**
     * Returns the total number of descendants that the element has.
     *
     * @return int
     */
    public function getTotalDescendants(): int;

    /**
     * Returns whether this element is an ancestor of another one.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isAncestorOf(ElementInterface $element): bool;

    /**
     * Returns whether this element is a descendant of another one.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isDescendantOf(ElementInterface $element): bool;

    /**
     * Returns whether this element is a direct parent of another one.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isParentOf(ElementInterface $element): bool;

    /**
     * Returns whether this element is a direct child of another one.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isChildOf(ElementInterface $element): bool;

    /**
     * Returns whether this element is a sibling of another one.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isSiblingOf(ElementInterface $element): bool;

    /**
     * Returns whether this element is the direct previous sibling of another one.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isPrevSiblingOf(ElementInterface $element): bool;

    /**
     * Returns whether this element is the direct next sibling of another one.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isNextSiblingOf(ElementInterface $element): bool;

    /**
     * Treats custom fields as array offsets.
     *
     * @param string|int $offset
     * @return bool
     */
    public function offsetExists($offset);

    /**
     * Returns the status of a given attribute.
     *
     * @param string $attribute
     * @return array|null
     * @since 3.4.0
     */
    public function getAttributeStatus(string $attribute);

    /**
     * Returns whether an attribute has changed since the element was first loaded.
     *
     * @param string $name
     * @return bool
     * @since 3.5.0
     */
    public function isAttributeDirty(string $name): bool;

    /**
     * Returns a list of attribute names that have changed since the element was first loaded.
     *
     * @return string[]
     * @since 3.4.0
     */
    public function getDirtyAttributes(): array;

    /**
     * Sets the list of dirty attribute names.
     *
     * @param string[] $names
     * @param bool $merge Whether these attributes should be merged with existing dirty attributes
     * @see getDirtyAttributes()
     * @since 3.5.0
     */
    public function setDirtyAttributes(array $names, bool $merge = true);

    /**
     * Returns whether the Title field should be shown as translatable in the UI.
     *
     * Note this method has no effect on whether titles will get copied over to other
     * sites when the element is actually getting saved. That is determined by [[getTitleTranslationKey()]].
     *
     * @return bool
     * @since 3.5.0
     */
    public function getIsTitleTranslatable(): bool;

    /**
     * Returns the description of the Title field’s translation support.
     *
     * @return string|null
     * @since 3.5.0
     */
    public function getTitleTranslationDescription();

    /**
     * Returns the Title’s translation key.
     *
     * When saving an element on a multi-site Craft install, if `$propagate` is `true` for [[\craft\services\Elements::saveElement()]],
     * then `getTitleTranslationKey()` will be called for each site the element should be propagated to.
     * If the method returns the same value as it did for the initial site, then the initial site’s title will be copied over
     * to the target site.
     *
     * @return string The translation key
     */
    public function getTitleTranslationKey(): string;

    /**
     * Returns whether a field is empty.
     *
     * @param string $handle
     * @return bool
     */
    public function isFieldEmpty(string $handle): bool;

    /**
     * Returns the element’s normalized custom field values, indexed by their handles.
     *
     * @param string[]|null $fieldHandles The list of field handles whose values
     * need to be returned. Defaults to null, meaning all fields’ values will be
     * returned. If it is an array, only the fields in the array will be returned.
     * @return array The field values (handle => value)
     */
    public function getFieldValues(array $fieldHandles = null): array;

    /**
     * Returns an array of the element’s serialized custom field values, indexed by their handles.
     *
     * @param string[]|null $fieldHandles The list of field handles whose values
     * need to be returned. Defaults to null, meaning all fields’ values will be
     * returned. If it is an array, only the fields in the array will be returned.
     * @return array
     */
    public function getSerializedFieldValues(array $fieldHandles = null): array;

    /**
     * Sets the element’s custom field values.
     *
     * @param array $values The custom field values (handle => value)
     */
    public function setFieldValues(array $values);

    /**
     * Returns the value for a given field.
     *
     * @param string $fieldHandle The field handle whose value needs to be returned
     * @return mixed The field value
     */
    public function getFieldValue(string $fieldHandle);

    /**
     * Sets the value for a given field.
     *
     * @param string $fieldHandle The field handle whose value needs to be set
     * @param mixed $value The value to set on the field
     */
    public function setFieldValue(string $fieldHandle, $value);

    /**
     * Returns the status of a given field.
     *
     * @param string $fieldHandle
     * @return array|null
     * @since 3.4.0
     */
    public function getFieldStatus(string $fieldHandle);

    /**
     * Returns whether a custom field value has changed since the element was first loaded.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     */
    public function isFieldDirty(string $fieldHandle): bool;

    /**
     * Returns a list of custom field handles that have changed since the element was first loaded.
     *
     * @return string[]
     * @since 3.4.0
     */
    public function getDirtyFields(): array;

    /**
     * Marks all fields and attributes as dirty.
     *
     * @since 3.4.10
     */
    public function markAsDirty();

    /**
     * Resets the record of dirty attributes and fields.
     *
     * @since 3.4.0
     */
    public function markAsClean();

    /**
     * Returns the cache tags that should be cleared when this element is saved.
     *
     * @return string[]
     * @since 3.5.0
     */
    public function getCacheTags(): array;

    /**
     * Sets the element’s custom field values, when the values have come from post data.
     *
     * @param string $paramNamespace The field param namespace
     */
    public function setFieldValuesFromRequest(string $paramNamespace);

    /**
     * Returns the namespace used by custom field params on the request.
     *
     * @return string|null The field param namespace
     */
    public function getFieldParamNamespace();

    /**
     * Sets the namespace used by custom field params on the request.
     *
     * @param string $namespace The field param namespace
     */
    public function setFieldParamNamespace(string $namespace);

    /**
     * Returns the name of the table this element’s content is stored in.
     *
     * @return string
     */
    public function getContentTable(): string;

    /**
     * Returns the field column prefix this element’s content uses.
     *
     * @return string
     */
    public function getFieldColumnPrefix(): string;

    /**
     * Returns the field context this element’s content uses.
     *
     * @return string
     */
    public function getFieldContext(): string;

    /**
     * Returns whether elements have been eager-loaded with a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return bool Whether elements have been eager-loaded with the given handle
     */
    public function hasEagerLoadedElements(string $handle): bool;

    /**
     * Returns the eager-loaded elements for a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return ElementInterface[]|null The eager-loaded elements, or null if they hadn't been eager-loaded
     */
    public function getEagerLoadedElements(string $handle);

    /**
     * Sets some eager-loaded elements on a given handle.
     *
     * @param string $handle The handle that was used to eager-load the elements
     * @param ElementInterface[] $elements The eager-loaded elements
     */
    public function setEagerLoadedElements(string $handle, array $elements);

    /**
     * Returns the count of eager-loaded elements for a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return int The eager-loaded element count
     * @since 3.4.0
     */
    public function getEagerLoadedElementCount(string $handle): int;

    /**
     * Sets the count of eager-loaded elements for a given handle.
     *
     * @param string $handle The handle to load the elements with in the future
     * @param int $count The eager-loaded element count
     * @since 3.4.0
     */
    public function setEagerLoadedElementCount(string $handle, int $count);

    /**
     * Returns whether the element’s content is "fresh" (unsaved and without validation errors).
     *
     * @return bool Whether the element’s content is fresh
     */
    public function getHasFreshContent(): bool;

    /**
     * Sets the revision creator ID to be saved.
     *
     * @param int|null $creatorId
     * @since 3.2.0
     */
    public function setRevisionCreatorId(int $creatorId = null);

    /**
     * Sets the revision notes to be saved.
     *
     * @param string|null $notes
     * @since 3.2.0
     */
    public function setRevisionNotes(string $notes = null);

    /**
     * Returns the element’s current revision, if one exists.
     *
     * @return ElementInterface|null
     * @since 3.2.0
     */
    public function getCurrentRevision();

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * Returns any attributes that should be included in the element’s DOM representation in the control panel.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::htmlAttributes()]]
     * instead of this method.
     * :::
     *
     * @param string $context The context that the element is being rendered in ('index', 'field', etc.)
     * @return array
     */
    public function getHtmlAttributes(string $context): array;

    /**
     * Returns the HTML that should be shown for a given attribute in Table View.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::tableAttributeHtml()]]
     * instead of this method.
     * :::
     *
     * @param string $attribute The attribute name.
     * @return string The HTML that should be shown for a given attribute in Table View.
     */
    public function getTableAttributeHtml(string $attribute): string;

    /**
     * Returns the HTML for the element’s editor HUD.
     *
     * @return string The HTML for the editor HUD
     */
    public function getEditorHtml(): string;

    /**
     * Returns the GraphQL type name for this element type.
     *
     * @return string
     * @since 3.3.0
     */
    public function getGqlTypeName(): string;

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     * @return bool Whether the element should be saved
     */
    public function beforeSave(bool $isNew): bool;

    /**
     * Performs actions after an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     */
    public function afterSave(bool $isNew);

    /**
     * Performs actions after an element is fully saved and propagated to other sites.
     *
     * ::: tip
     * This will get called regardless of whether `$propagate` is `true` or `false` for [[\craft\services\Elements::saveElement()]].
     * :::
     *
     * @param bool $isNew Whether the element is brand new
     * @since 3.2.0
     */
    public function afterPropagate(bool $isNew);

    /**
     * Performs actions before an element is deleted.
     *
     * @return bool Whether the element should be deleted
     */
    public function beforeDelete(): bool;

    /**
     * Performs actions after an element is deleted.
     */
    public function afterDelete();

    /**
     * Performs actions before an element is restored.
     *
     * @return bool Whether the element should be restored
     * @since 3.1.0
     */
    public function beforeRestore(): bool;

    /**
     * Performs actions after an element is restored.
     *
     * @since 3.1.0
     */
    public function afterRestore();

    /**
     * Performs actions before an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     * @return bool Whether the element should be moved within the structure
     */
    public function beforeMoveInStructure(int $structureId): bool;

    /**
     * Performs actions after an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     */
    public function afterMoveInStructure(int $structureId);
}
