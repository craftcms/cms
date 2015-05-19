<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\models\Content;
use craft\app\models\FieldLayout;


/**
 * ElementInterface defines the common interface to be implemented by element classes.
 *
 * A class implementing this interface should also use [[ElementTrait]] and [[ContentTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ElementInterface extends ComponentInterface
{
	// Static
	// =========================================================================

	/**
	 * Returns whether elements of this type will be storing any data in the `content` table (tiles or custom fields).
	 *
	 * @return bool Whether elements of this type will be storing any data in the `content` table.
	 */
	public static function hasContent();

	/**
	 * Returns whether elements of this type have traditional titles.
	 *
	 * @return bool Whether elements of this type have traditional titles.
	 */
	public static function hasTitles();

	/**
	 * Returns whether elements of this type store data on a per-locale basis.
	 *
	 * If this returns `true`, the element’s [[getLocales()]] method will
	 * be responsible for defining which locales its data should be stored in.
	 *
	 * @return bool Whether elements of this type store data on a per-locale basis.
	 */
	public static function isLocalized();

	/**
	 * Returns whether elements of this type have statuses.
	 *
	 * If this returns `true`, the element index template will show a Status menu by default, and your elements will
	 * get status indicator icons next to them.
	 *
	 * Use [[getStatuses()]] to customize which statuses the elements might have.
	 *
	 * @return bool Whether elements of this type have statuses.
	 * @see getStatuses()
	 */
	public static function hasStatuses();

	/**
	 * Creates an [[ElementQueryInterface|ElementQuery]] instance for query purpose.
	 *
	 * The returned [[ElementQueryInterface|ElementQuery]] instance can be further customized by calling
	 * methods defined in [[ElementQueryInterface]] before `one()` or `all()` is called to return
	 * populated [[ElementInterface]] instances. For example,
	 *
	 * ```php
	 * // Find the entry whose ID is 5
	 * $entry = Entry::find()->id(5)->one();
	 *
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
	 * @return ElementQueryInterface|ElementQuery The newly created [[ElementQueryInterface|ElementQuery]] instance.
	 */
	public static function find();

	/**
	 * Returns a single element instance by a primary key or a set of element criteria parameters.
	 *
	 * The method accepts:
	 *
	 *  - an integer: query by a single ID value and return the corresponding element (or null if not found).
	 *  - an array of name-value pairs: query by a set of parameter values and return the first element
	 *    matching all of them (or null if not found).
	 *
	 * Note that this method will automatically call the `one()` method and return an
	 * [[ElementInterface|\craft\app\base\Element]] instance. For example,
	 *
	 * ```php
	 * // find a single entry whose ID is 10
	 * $entry = Entry::findOne(10);
	 *
	 * // the above code is equivalent to:
	 * $entry = Entry::find->id(10)->one();
	 *
	 * // find the first user whose email ends in "example.com"
	 * $user = User::findOne(['email' => '*example.com']);
	 *
	 * // the above code is equivalent to:
	 * $user = User::find()->email('*example.com')->one();
	 * ```
	 *
	 * @param mixed $criteria The element ID or a set of element criteria parameters
	 * @return static Element instance matching the condition, or null if nothing matches.
	 */
	public static function findOne($criteria = null);

	/**
	 * Returns a list of elements that match the specified ID(s) or a set of element criteria parameters.
	 *
	 * The method accepts:
	 *
	 *  - an integer: query by a single ID value and return an array containing the corresponding element
	 *    (or an empty array if not found).
	 *  - an array of integers: query by a list of ID values and return the corresponding elements (or an
	 *    empty array if none was found).
	 *    Note that an empty array will result in an empty result as it will be interpreted as a search for
	 *    primary keys and not an empty set of element criteria parameters.
	 *  - an array of name-value pairs: query by a set of parameter values and return an array of elements
	 *    matching all of them (or an empty array if none was found).
	 *
	 * Note that this method will automatically call the `all()` method and return an array of
	 * [[ElementInterface|\craft\app\base\Element]] instances. For example,
	 *
	 * ```php
	 * // find the entries whose ID is 10
	 * $entries = Entry::findAll(10);
	 *
	 * // the above code is equivalent to:
	 * $entries = Entry::find()->id(10)->all();
	 *
	 * // find the entries whose ID is 10, 11 or 12.
	 * $entries = Entry::findAll([10, 11, 12]);
	 *
	 * // the above code is equivalent to:
	 * $entries = Entry::find()->id([10, 11, 12]])->all();
	 *
	 * // find users whose email ends in "example.com"
	 * $users = User::findAll(['email' => '*example.com']);
	 *
	 * // the above code is equivalent to:
	 * $users = User::find()->email('*example.com')->all();
	 * ```
	 *
	 * @param mixed $criteria The element ID, an array of IDs, or a set of element criteria parameters
	 * @return array an array of Element instances, or an empty array if nothing matches.
	 */
	public static function findAll($criteria = null);

	/**
	 * Returns all of the possible statuses that elements of this type may have.
	 *
	 * This method will be called when populating the Status menu on element indexes, for element types whose
	 * [[hasStatuses()]] method returns `true`. It will also be called when [[\craft\app\elements\ElementQuery]] is querying for
	 * elements, to ensure that its “status” parameter is set to a valid status.
	 *
	 * It should return an array whose keys are the status values, and values are the human-facing status labels.
	 *
	 * You can customize the database query condition that should be applied for your custom statuses from
	 * [[getElementQueryStatusCondition()]].
	 *
	 * @return array|null
	 * @see hasStatuses()
	 */
	public static function getStatuses();

	/**
	 * Returns the keys of the sources that elements of this type may belong to.
	 *
	 * This defines what will show up in the source list on element indexes and element selector modals.
	 *
	 * Each item in the array should have a key that identifies the source’s key (e.g. "section:3"), and should be set
	 * to an array that has the following keys:
	 *
	 * - **`label`** – The human-facing label of the source.
	 * - **`criteria`** – An array of element criteria parameters that the source should use when the source is selected.
	 *   (Optional)
	 * - **`data`** – An array of `data-X` attributes that should be set on the source’s `<a>` tag in the source list’s,
	 *   HTML, where each key is the name of the attribute (without the “data-” prefix), and each value is the value of
	 *   the attribute. (Optional)
	 * - **`hasThumbs`** – A boolean that defines whether this source supports Thumbs View. (Use your element’s
	 *   [[getThumbUrl()]] or [[getIconUrl()]] methods to define your elements’ thumb/icon URLs.) (Optional)
	 * - **`structureId`** – The ID of the Structure that contains the elements in this source. If set, Structure View
	 *   will be available to this source. (Optional)
	 * - **`newChildUrl`** – The URL that should be loaded when a usel select’s the “New child” menu option on an
	 *   element in this source while it is in Structure View. (Optional)
	 * - **`nested`** – An array of sources that are nested within this one. Each nested source can have the same keys
	 *   as top-level sources.
	 *
	 * @param string|null $context The context ('index' or 'modal').
	 *
	 * @return string[]|false The source keys.
	 */
	public static function getSources($context = null);

	/**
	 * Returns a source by its key and context.
	 *
	 * @param string $key     The source’s key.
	 * @param string $context The context ('index' or 'modal').
	 *
	 * @return array|null
	 */
	public static function getSourceByKey($key, $context = null);

	/**
	 * Returns the available element actions for a given source (if one is provided).
	 *
	 * The actions can either be represented by their class handle (e.g. 'SetStatus'), or by an
	 * [[ElementActionInterface]] instance.
	 *
	 * @param string|null $source The selected source’s key, if any.
	 *
	 * @return array|null The available element actions.
	 */
	public static function getAvailableActions($source = null);

	/**
	 * Defines which element attributes should be searchable.
	 *
	 * This method should return an array of attribute names that can be accessed on your elements.
	 * [[\craft\app\services\Search]] will call this method when it is indexing keywords for one of your elements,
	 * and for each attribute it returns, it will fetch the corresponding property’s value on the element.
	 *
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
	 * @return array The
	 */
	public static function defineSearchableAttributes();

	/**
	 * Returns the element index HTML.
	 *
	 * @param ElementQueryInterface $elementQuery
	 * @param array                 $disabledElementIds
	 * @param array                 $viewState
	 * @param string|null           $sourceKey
	 * @param string|null           $context
	 * @param bool                  $includeContainer
	 * @param bool                  $showCheckboxes
	 *
	 * @return string
	 */
	public static function getIndexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);

	/**
	 * Defines the attributes that elements can be sorted by.
	 *
	 * This method should return an array, where the keys reference database column names that should be sorted on,
	 * and where the values define the user-facing labels.
	 *
	 * ```php
	 * return [
	 *     'columnName1' => Craft::t('app', 'Attribute Label 1'),
	 *     'columnName2' => Craft::t('app', 'Attribute Label 2'),
	 * ];
	 * ```
	 *
	 * If you want to sort by multilple columns simultaneously, you can specify multiple column names in the key,
	 * separated by commas.
	 *
	 * ```php
	 * return [
	 *     'columnName1, columnName2 asc' => Craft::t('app', 'Attribute Label 1'),
	 *     'columnName3'                  => Craft::t('app', 'Attribute Label 2'),
	 * ];
	 * ```
	 *
	 * If you do that, you can specify the sort direction for the subsequent columns (`asc` or `desc`. There is no point
	 * in specifying the sort direction for the first column, though, since the end user has full control over that.
	 *
	 * Note that this method will only get called once for the entire index; not each time that a new source is
	 * selected.
	 *
	 * @retrun array The attributes that elements can be sorted by.
	 */
	public static function defineSortableAttributes();

	/**
	 * Defines the columns that can be shown in table views.
	 *
	 * This method should return an array whose keys map to attribute names and database columns that can be sorted
	 * against when querying for elements, and whose values make up the table’s column headers.
	 *
	 * The *first* item that this array returns will just identify the database column name, and the table column’s
	 * header, but will **not** have any effect on what shows up in the table’s body. That’s because the first column is
	 * reserved for displaying whatever your element’s __toString() method returns.
	 *
	 * All other items besides the first one will also define which element attribute should be shown within the data
	 * cells. (The actual HTML to be shown can be customized with [[getTableAttributeHtml()]].)
	 *
	 * @param string|null $source The selected source’s key, if any.
	 *
	 * @return array The table attributes.
	 */
	public static function defineTableAttributes($source = null);

	/**
	 * Returns the HTML that should be shown for a given element’s attribute in Table View.
	 *
	 * This method can be used to completely customize what actually shows up within the table’s body for a given
	 * attribtue, rather than simply showing the attribute’s raw value.
	 *
	 * For example, if your elements have an “email” attribute that you want to wrap in a `mailto:` link, your
	 * getTableAttributesHtml() method could do this:
	 *
	 * ```php
	 * switch ($attribute)
	 * {
	 *     case 'email':
	 *     {
	 *         if ($element->email)
	 *         {
	 *             return '<a href="mailto:'.$element->email.'">'.$element->email.'</a>';
	 *         }
	 *
	 *         break;
	 *     }
	 *     default:
	 *     {
	 *         return parent::getTableAttributeHtml($element, $attribute);
	 *     }
	 * }
	 * ```
	 *
	 * Element::getTableAttributeHtml() provides a couple handy attribute checks by default, so it is a good
	 * idea to let the parent method get called (as shown above). They are:
	 *
	 * - If the attribute name is ‘uri’, it will be linked to the front-end URL.
	 * - If the attribute name is ‘dateCreated’ or ‘dateUpdated’, the date will be formatted according to the active
	 *   locale.
	 *
	 * @param ElementInterface $element   The element.
	 * @param string           $attribute The attribute name.
	 *
	 * @return string
	 */
	public static function getTableAttributeHtml(ElementInterface $element, $attribute);

	/**
	 * Returns the fields that should take part in an upcoming elements qurery.
	 *
	 * These fields will get their own criteria parameters in the [[ElementQueryInterface]] that gets passed in,
	 * their field types will each have an opportunity to help build the element query, and their columns in the content
	 * table will be selected by the query (for those that have one).
	 *
	 * If a field has its own column in the content table, but the column name is prefixed with something besides
	 * “field_”, make sure you set the `columnPrefix` attribute on the [[\craft\app\base\Field]], so
	 * [[\craft\app\services\Elements::buildElementsQuery()]] knows which column to select.
	 *
	 * @param ElementQueryInterface|ElementQuery $query
	 *
	 * @return \craft\app\base\FieldInterface[]
	 */
	public static function getFieldsForElementsQuery(ElementQueryInterface $query);

	/**
	 * Returns the element query condition for a custom status parameter value.
	 *
	 * If the ElementQuery’s [[\craft\app\elements\ElementQuery::status status]] parameter is set to something besides
	 * 'enabled' or 'disabled', and it’s one of the statuses that you’ve defined in [[getStatuses()]], this method
	 * is where you can turn that custom status into an actual SQL query condition.
	 *
	 * For example, if you support a status called “pending”, which maps back to a `pending` database column that will
	 * either be 0 or 1, this method could do this:
	 *
	 * ```php
	 * switch ($status)
	 * {
	 *     case 'pending':
	 *     {
	 *         $query->andWhere('mytable.pending = 1');
	 *         break;
	 *     }
	 * }
	 * ```
	 *
	 * @param ElementQueryInterface|ElementQuery $query  The database query.
	 * @param string                             $status The custom status.
	 *
	 * @return string|false
	 */
	public static function getElementQueryStatusCondition(ElementQueryInterface $query, $status);

	/**
	 * Returns the HTML for an editor HUD for the given element.
	 *
	 * @param ElementInterface $element The element being edited.
	 *
	 * @return string The HTML for the editor HUD.
	 */
	public static function getEditorHtml(ElementInterface $element);

	/**
	 * Saves a given element.
	 *
	 * This method will be called when an Element Editor’s Save button is clicked. It should just wrap your service’s
	 * saveX() method.
	 *
	 * @param ElementInterface $element The element being saved.
	 * @param array  $params  Any element params found in the POST data.
	 *
	 * @return bool Whether the element was saved successfully.
	 */
	public static function saveElement(ElementInterface $element, $params);

	/**
	 * Returns the route for a given element.
	 *
	 * @param static The matched element.
	 *
	 * @return mixed Can be false if no special action should be taken, a string if it should route to a template path,
	 *               or an array that can specify a controller action path, params, etc.
	 */
	public static function getElementRoute(ElementInterface $element);

	/**
	 * Performs actions after an element has been moved within a structure.
	 *
	 * @param ElementInterface $element     The element that was moved.
	 * @param int              $structureId The ID of the structure that it moved within.
	 *
	 * @return null
	 */
	public static function onAfterMoveElementInStructure(ElementInterface $element, $structureId);

	// Public Methods
	// =========================================================================

	/**
	 * Returns the element’s ID.
	 *
	 * @return int|null
	 * @internal This method is required by [[\yii\web\IdentityInterface]], but might as well
	 * go here rather than only in [[\craft\app\elements\User]].
	 */
	public function getId();

	/**
	 * Returns the field layout used by this element.
	 *
	 * @return FieldLayout|null
	 */
	public function getFieldLayout();

	/**
	 * Returns the locale IDs this element is available in.
	 *
	 * @return string[]
	 */
	public function getLocales();

	/**
	 * Returns the URL format used to generate this element’s URL.
	 *
	 * @return string|null
	 */
	public function getUrlFormat();

	/**
	 * Returns the element’s full URL.
	 *
	 * @return string|null
	 */
	public function getUrl();

	/**
	 * Returns an anchor pre-filled with this element’s URL and title.
	 *
	 * @return \Twig_Markup
	 */
	public function getLink();

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
	public function isEditable();

	/**
	 * Returns the element’s CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl();

	/**
	 * Returns the URL to the element’s thumbnail, if there is one.
	 *
	 * @param int|null $size
	 *
	 * @return string|false
	 */
	public function getThumbUrl($size = null);

	/**
	 * Returns the URL to the element’s icon image, if there is one.
	 *
	 * @param int|null $size
	 *
	 * @return string|false
	 */
	public function getIconUrl($size = null);

	/**
	 * Returns the element’s status.
	 *
	 * @return string|null
	 */
	public function getStatus();

	/**
	 * Returns the next element relative to this one, from a given set of criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return ElementInterface|Element|null
	 */
	public function getNext($criteria = false);

	/**
	 * Returns the previous element relative to this one, from a given set of criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return ElementInterface|Element|null
	 */
	public function getPrev($criteria = false);

	/**
	 * Sets the default next element.
	 *
	 * @param ElementInterface|Element|false $element
	 *
	 * @return null
	 */
	public function setNext($element);

	/**
	 * Sets the default previous element.
	 *
	 * @param ElementInterface|Element|false $element
	 *
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
	 *
	 * @return null
	 */
	public function setParent($parent);

	/**
	 * Returns the ID of the structure that the element is associated with, if any.
	 *
	 * @return integer|null The ID of the structure, or null if there isn’t one
	 */
	public function getStructureId();

	/**
	 * Sets the ID of the structure that the element is associated with.
	 *
	 * @param integer|null $structureId The ID of the structure, or null to remove the previous association.
	 */
	public function setStructureId($structureId);

	/**
	 * Returns the element’s ancestors.
	 *
	 * @param int|null $dist
	 *
	 * @return ElementQueryInterface|ElementQuery
	 */
	public function getAncestors($dist = null);

	/**
	 * Returns the element’s descendants.
	 *
	 * @param int|null $dist
	 *
	 * @return ElementQueryInterface|ElementQuery
	 */
	public function getDescendants($dist = null);

	/**
	 * Returns the element’s children.
	 *
	 * @return ElementQueryInterface|ElementQuery
	 */
	public function getChildren();

	/**
	 * Returns all of the element’s siblings.
	 *
	 * @return ElementQueryInterface|ElementQuery
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
	public function hasDescendants();

	/**
	 * Returns the total number of descendants that the element has.
	 *
	 * @return bool
	 */
	public function getTotalDescendants();

	/**
	 * Returns whether this element is an ancestor of another one.
	 *
	 * @param ElementInterface $element
	 *
	 * @return bool
	 */
	public function isAncestorOf(ElementInterface $element);

	/**
	 * Returns whether this element is a descendant of another one.
	 *
	 * @param ElementInterface $element
	 *
	 * @return bool
	 */
	public function isDescendantOf(ElementInterface $element);

	/**
	 * Returns whether this element is a direct parent of another one.
	 *
	 * @param ElementInterface $element
	 *
	 * @return bool
	 */
	public function isParentOf(ElementInterface $element);

	/**
	 * Returns whether this element is a direct child of another one.
	 *
	 * @param ElementInterface $element
	 *
	 * @return bool
	 */
	public function isChildOf(ElementInterface $element);

	/**
	 * Returns whether this element is a sibling of another one.
	 *
	 * @param ElementInterface $element
	 *
	 * @return bool
	 */
	public function isSiblingOf(ElementInterface $element);

	/**
	 * Returns whether this element is the direct previous sibling of another one.
	 *
	 * @param ElementInterface $element
	 *
	 * @return bool
	 */
	public function isPrevSiblingOf(ElementInterface $element);

	/**
	 * Returns whether this element is the direct next sibling of another one.
	 *
	 * @param ElementInterface $element
	 *
	 * @return bool
	 */
	public function isNextSiblingOf(ElementInterface $element);

	/**
	 * Returns the element’s title.
	 *
	 * @return string
	 */
	public function getTitle();

	/**
	 * Treats custom fields as array offsets.
	 *
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset);

	/**
	 * Returns the content for the element.
	 *
	 * @return Content
	 */
	public function getContent();

	/**
	 * Sets the content for the element.
	 *
	 * @param Content|array $content
	 *
	 * @return null
	 */
	public function setContent($content);

	/**
	 * Sets the content from post data.
	 *
	 * @param array|string $content The array of field values, or the post location of the content
	 *
	 * @return null
	 */
	public function setContentFromPost($content);

	/**
	 * Returns the raw content from the post data, as it was given to [[setContentFromPost]]
	 *
	 * @return array
	 */
	public function getContentFromPost();

	/**
	 * Returns the location in POST that the content was pulled from.
	 *
	 * @return string|null
	 */
	public function getContentPostLocation();

	/**
	 * Sets the location in POST that the content was pulled from.
	 *
	 * @param $contentPostLocation
	 *
	 * @return string|null
	 */
	public function setContentPostLocation($contentPostLocation);

	/**
	 * Returns the prepped content for a given field.
	 *
	 * @param string $fieldHandle
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function getFieldValue($fieldHandle);

	/**
	 * Returns the name of the table this element’s content is stored in.
	 *
	 * @return string
	 */
	public function getContentTable();

	/**
	 * Returns the field column prefix this element’s content uses.
	 *
	 * @return string
	 */
	public function getFieldColumnPrefix();

	/**
	 * Returns the field context this element’s content uses.
	 *
	 * @return string
	 */
	public function getFieldContext();

	// Events
	// -------------------------------------------------------------------------

	/**
	 * This method is called right before the element is saved, and returns whether the element should be saved.
	 *
	 * @return boolean Whether the element should be saved
	 */
	public function beforeSave();

	/**
	 * This method is called right after the element is saved.
	 */
	public function afterSave();
}
