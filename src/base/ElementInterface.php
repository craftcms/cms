<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use craft\app\db\Query;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\Field as FieldModel;
use craft\app\models\FieldLayout;


/**
 * This interface defines the contract that all element types must implement via [[Element]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ElementInterface
{
	// Public Methods
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
	 * Returns all of the possible statuses that elements of this type may have.
	 *
	 * This method will be called when populating the Status menu on element indexes, for element types whose
	 * [[hasStatuses()]] method returns `true`. It will also be called when [[\craft\app\services\Elements]] is querying for
	 * elements, to ensure that the [[ElementCriteriaModel]]’s “status” parameter is set to a valid status.
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
	 * - **`criteria`** – An array of criteria parameters that the source should use when the source is selected.
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
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @param array                $viewState
	 * @param string|null          $sourceKey
	 * @param string|null          $context
	 * @param bool                 $includeContainer
	 * @param bool                 $showCheckboxes
	 *
	 * @return string
	 */
	public static function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);

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
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * This method returns an array which will get merged into the array defined in
	 * [[ElementCriteriaModel::defineAttributes()]], when new ElementCriteriaModel instances are created targeting
	 * this element type (generally from [[\craft\app\services\Elements::getCriteria() Craft::$app->elements->getCriteria()]]).
	 *
	 * If this method were to return the following:
	 *
	 * ```php
	 * return [
	 *     'foo' => AttributeType::String,
	 *     'bar' => AttributeType::String,
	 * ];
	 * ```
	 *
	 * then when someone creates a new ElementCriteriaModel instance targeting this elmeent type, they will be able to
	 * do this:
	 *
	 * ```php
	 * $criteria = Craft::$app->elements->getCriteria('ThisElementType');
	 * $criteria->foo = 'FooParamValue';
	 * $criteria->bar = 'BarParamValue';
	 * ```
	 *
	 * You can check for these custom criteria attributes, and factor their values into the actual database query,
	 * from [[modifyElementsQuery()]].
	 *
	 * @return array Custom criteria attributes.
	 */
	public static function defineCriteriaAttributes();

	/**
	 * Returns the content table name that should be joined into an elements query for a given element criteria.
	 *
	 * This method will get called from [[\craft\app\services\Elements::buildElementsQuery()]] as it is building out a database
	 * query to fetch elements with a given criteria. It will only be called if [[hasContent()]] returns `true`.
	 *
	 * If this method returns `false`, no content table will be joined in, and it will be up to the element’s
	 * [[getContent()]] method to fetch content rows on demand.
	 *
	 * @param ElementCriteriaModel The element criteria.
	 *
	 * @return string|false The content table name, or `false` if it cannot be determined.
	 */
	public static function getContentTableForElementsQuery(ElementCriteriaModel $criteria);

	/**
	 * Returns the fields that should take part in an upcoming elements qurery.
	 *
	 * These fields will get their own parameters in the [[ElementCriteriaModel]] that gets passed in,
	 * their field types will each have an opportunity to help build the element query, and their columns in the content
	 * table will be selected by the query (for those that have one).
	 *
	 * If a field has its own column in the content table, but the column name is prefixed with something besides
	 * “field_”, make sure you set the `columnPrefix` attribute on the [[FieldModel]], so
	 * [[\craft\app\services\Elements::buildElementsQuery()]] knows which column to select.
	 *
	 * @param ElementCriteriaModel
	 *
	 * @return FieldModel[]
	 */
	public static function getFieldsForElementsQuery(ElementCriteriaModel $criteria);

	/**
	 * Returns the element query condition for a custom status criteria.
	 *
	 * If the ElementCriteriaModel’s [[ElementCriteriaModel::status status]] parameter is set to something besides
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
	 * @param Query  $query  The database query.
	 * @param string $status The custom status.
	 *
	 * @return string|false
	 */
	public static function getElementQueryStatusCondition(Query $query, $status);

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * If your element type is storing additional data in its own table, this method is the place to join that table in.
	 *
	 * ```php
	 * $query
	 *     ->addSelect('mytable.foo, mytable.bar')
	 *     ->innerJoin('{{%mytable}} mytable', 'mytable.id = elements.id');
	 * ```
	 *
	 * This is also where you get to check the [[ElementCriteriaModel]] for all the custom attributes that this
	 * element type supports via {@defineCriteriaAttributes()}, and modify the database query to reflect those
	 * parameters.
	 *
	 * ```php
	 * if ($criteria->foo)
	 * {
	 *     $query->andWhere(DbHelper::parseParam('mytable.foo', $criteria->foo, $query->params));
	 * }
	 *
	 * if ($criteria->bar)
	 * {
	 *     $query->andWhere(DbHelper::parseParam('mytable.bar', $criteria->bar, $query->params));
	 * }
	 * ```
	 *
	 * If you are able to determine from the element criteria’s paramteers that there’s no way that the query is going
	 * to match any elements, you can have it return `false`. The query will be stopped before it ever gets a chance to
	 * execute.
	 *
	 * @param Query                $query    The database query currently being built to find the elements.
	 * @param ElementCriteriaModel $criteria The criteria that is being used to find the elements.
	 *
	 * @return false|null `false` in the event that the method is sure that no elements are going to be found.
	 */
	public static function modifyElementsQuery(Query $query, ElementCriteriaModel $criteria);

	/**
	 * Populates an element based on a query result.
	 *
	 * This method is called by [[\craft\app\services\Elements::findElements()]] after it has finished fetching all of the
	 * matching elements’ rows from the database.
	 *
	 * For each row of data returned by the query, it will call this method on the element type, and it is up to this
	 * method to take that array of raw data from the database, and populate a new element with it.
	 *
	 * You should be able to accomplish this with a single line:
	 *
	 * ```php
	 * return MyElementTypeModel::populateModel($row);
	 * ```
	 *
	 * @param array $row The row of data in the database query result.
	 *
	 * @return ElementInterface The element, populated with the data in $row.
	 */
	public static function populateElementModel($row);

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

	// Instance Methods
	// -------------------------------------------------------------------------

	/**
	 * Returns the element’s ID.
	 *
	 * @return int|null
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
	 * @return ElementCriteriaModel|null
	 */
	public function getNext($criteria = false);

	/**
	 * Returns the previous element relative to this one, from a given set of criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return ElementCriteriaModel|null
	 */
	public function getPrev($criteria = false);

	/**
	 * Sets the default next element.
	 *
	 * @param ElementInterface|false $element
	 *
	 * @return null
	 */
	public function setNext($element);

	/**
	 * Sets the default previous element.
	 *
	 * @param ElementInterface|false $element
	 *
	 * return void
	 */
	public function setPrev($element);

	/**
	 * Get the element’s parent.
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
	 * Returns the element’s ancestors.
	 *
	 * @param int|null $dist
	 *
	 * @return ElementCriteriaModel
	 */
	public function getAncestors($dist = null);

	/**
	 * Returns the element’s descendants.
	 *
	 * @param int|null $dist
	 *
	 * @return ElementCriteriaModel
	 */
	public function getDescendants($dist = null);

	/**
	 * Returns the element’s children.
	 *
	 * @return ElementCriteriaModel
	 */
	public function getChildren();

	/**
	 * Returns all of the element’s siblings.
	 *
	 * @return ElementCriteriaModel
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
	 * @inheritDoc Model::getAttribute()
	 *
	 * @param string $name
	 * @param bool   $flattenValue
	 *
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false);

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
	 * Sets the content from post data, calling prepValueFromPost() on the field types.
	 *
	 * @param array|string $content
	 *
	 * @return null
	 */
	public function setContentFromPost($content);

	/**
	 * Returns the raw content from the post data, before it was passed through [[prepValueFromPost()]].
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
}
