<?php
namespace Craft;

/**
 * This interface defines the contract that all element types must implement via {@link BaseElementType}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.elementtypes
 * @since     1.0
 */
interface IElementType extends IComponentType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether this element type will be storing any data in the `content` table (tiles or custom fields).
	 *
	 * @return bool Whether the element type has content. Default is `false`.
	 */
	public function hasContent();

	/**
	 * Returns whether this element type has titles.
	 *
	 * @return bool Whether the element type has titles. Default is `false`.
	 */
	public function hasTitles();

	/**
	 * Returns whether this element type stores data on a per-locale basis.
	 *
	 * If this returns `true`, the element model’s {@link BaseElementModel::getLocales() getLocales()} method will
	 * be responsible for defining which locales its data should be stored in.
	 *
	 * @return bool Whether the element type is localized. Default is `false`.
	 */
	public function isLocalized();

	/**
	 * Returns whether this element type can have statuses.
	 *
	 * If this returns `true`, the element index template will show a Status menu by default, and your elements will
	 * get status indicator icons next to them.
	 *
	 * Use {@link getStatuses()} to customize which statuses the elements might have.
	 *
	 * @return bool Whether the element type has statuses. Default is `false`.
	 */
	public function hasStatuses();

	/**
	 * Returns all of the possible statuses that elements of this type may have.
	 *
	 * This method will be called when populating the Status menu on element indexes, for element types whose
	 * {@link hasStatuses()} method returns `true`. It will also be called when {@link ElementsService} is querying for
	 * elements, to ensure that the {@link ElementCriteriaModel}’s “status” parameter is set to a valid status.
	 *
	 * It should return an array whose keys are the status values, and values are the human-facing status labels.
	 *
	 * You can customize the database query condition that should be applied for your custom statuses from
	 * {@link getElementQueryStatusCondition()}.
	 *
	 * @return array|null
	 */
	public function getStatuses();

	/**
	 * Returns this element type's sources.
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
	 * - **`defaultSort`** – A string identifying the sort attribute that should be selected by default, or an array where
	 *   the first value identifies the sort attribute, and the second determines which direction to sort by. (Optional)
	 * - **`hasThumbs`** – A boolean that defines whether this source supports Thumbs View. (Use your element model’s
	 *   {@link BaseElementModel::getThumbUrl() getThumbUrl()} method to define your elements’ thumb URL.) (Optional)
	 * - **`structureId`** – The ID of the Structure that contains the elements in this source. If set, Structure View
	 *   will be available to this source. (Optional)
	 * - **`newChildUrl`** – The URL that should be loaded when a usel select’s the “New child” menu option on an
	 *   element in this source while it is in Structure View. (Optional)
	 * - **`nested`** – An array of sources that are nested within this one. Each nested source can have the same keys
	 *   as top-level sources.
	 *
	 * @param string|null $context The context ('index' or 'modal').
	 *
	 * @return array|false The element type's sources.
	 */
	public function getSources($context = null);

	/**
	 * Returns a source by its key and context.
	 *
	 * @param string $key     The source’s key.
	 * @param string $context The context ('index' or 'modal').
	 *
	 * @return array|null
	 */
	public function getSource($key, $context = null);

	/**
	 * Returns the available element actions for a given source (if one is provided).
	 *
	 * The actions can either be represented by their class handle (e.g. 'SetStatus'), or by an
	 * {@link IElementAction} instance.
	 *
	 * @param string|null $source The selected source’s key, if any.
	 *
	 * @return array|null The available element actions.
	 */
	public function getAvailableActions($source = null);

	/**
	 * Defines which element model attributes should be searchable.
	 *
	 * This method should return an array of attribute names that can be accessed on your
	 * {@link BaseElementModel element model} (for example, the attributes defined by your element model’s
	 * {@link BaseElementType::defineAttributes() defineAttributes()} method). {@link SearchService} will call this
	 * method when it is indexing keywords for one of your elements, and for each attribute it returns, it will fetch
	 * the corresponding property’s value on the element.
	 *
	 * For example, if your elements have a “color” attribute which you want to be indexed, this method could return:
	 *
	 * ```php
	 * return array('color');
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
	 * @return array The attributes that should be searchable
	 */
	public function defineSearchableAttributes();

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
	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);

	/**
	 * Defines the attributes that elements can be sorted by.
	 *
	 * This method should return an array, where the keys reference database column names that should be sorted on,
	 * and where the values define the user-facing labels.
	 *
	 * ```php
	 * return array(
	 *     'columnName1' => Craft::t('Attribute Label 1'),
	 *     'columnName2' => Craft::t('Attribute Label 2'),
	 * );
	 * ```
	 *
	 * If you want to sort by multilple columns simultaneously, you can specify multiple column names in the key,
	 * separated by commas.
	 *
	 * ```php
	 * return array(
	 *     'columnName1, columnName2 asc' => Craft::t('Attribute Label 1'),
	 *     'columnName3'                  => Craft::t('Attribute Label 2'),
	 * );
	 * ```
	 *
	 * If you do that, you can specify the sort direction for the subsequent columns (`asc` or `desc`. There is no point
	 * in specifying the sort direction for the first column, though, since the end user has full control over that.
	 *
	 * Note that this method will only get called once for the entire index; not each time that a new source is
	 * selected.
	 *
	 * @return array The attributes that elements can be sorted by.
	 */
	public function defineSortableAttributes();

	/**
	 * Defines all of the available columns that can be shown in table views.
	 *
	 * This method should return an array whose keys map to attribute names and database columns that can be sorted
	 * against when querying for elements, and whose values make up the table’s column headers.
	 *
	 * The *first* item that this array returns will just identify the database column name, and the table column’s
	 * header, but will **not** have any effect on what shows up in the table’s body. That’s because the first column is
	 * reserved for displaying whatever your element model’s {@link BaseElementModel::__toString() __toString()} method
	 * returns (the string representation of the element).
	 *
	 * All other items besides the first one will also define which element attribute should be shown within the data
	 * cells. (The actual HTML to be shown can be customized with {@link getTableAttributeHtml()}.)
	 *
	 * @return array The table attributes.
	 */
	public function defineAvailableTableAttributes();

	/**
	 * Returns the list of table attribute keys that should be shown by default.
	 *
	 * This method should return an array where each element in the array maps to one of the keys of the array returned
	 * by {@link defineAvailableTableAttributes()}.
	 *
	 * @param string|null $source The selected source’s key, if any.
	 *
	 * @return array The table attribute keys.
	 */
	public function getDefaultTableAttributes($source = null);

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
	 * BaseElementType::getTableAttributeHtml() provides a couple handy attribute checks by default, so it is a good
	 * idea to let the parent method get called (as shown above). They are:
	 *
	 * - If the attribute name is ‘uri’, it will be linked to the front-end URL.
	 * - If the attribute name is ‘dateCreated’ or ‘dateUpdated’, the date will be formatted according to the active
	 *   locale.
	 *
	 * @param BaseElementModel $element   The element.
	 * @param string           $attribute The attribute name.
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute);

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * This method returns an array which will get merged into the array defined in
	 * {@link ElementCriteriaModel::defineAttributes()}, when new ElementCriteriaModel instances are created targeting
	 * this element type (generally from {@link ElementsService::getCriteria() craft()->elements->getCriteria()}).
	 *
	 * If this method were to return the following:
	 *
	 * ```php
	 * return array(
	 *     'foo' => AttributeType::String,
	 *     'bar' => AttributeType::String,
	 * );
	 * ```
	 *
	 * then when someone creates a new ElementCriteriaModel instance targeting this elmeent type, they will be able to
	 * do this:
	 *
	 * ```php
	 * $criteria = craft()->elements->getCriteria('ThisElementType');
	 * $criteria->foo = 'FooParamValue';
	 * $criteria->bar = 'BarParamValue';
	 * ```
	 *
	 * You can check for these custom criteria attributes, and factor their values into the actual database query,
	 * from {@link modifyElementsQuery()}.
	 *
	 * @return array Custom criteria attributes.
	 */
	public function defineCriteriaAttributes();

	/**
	 * Returns the content table name that should be joined into an elements query for a given element criteria.
	 *
	 * This method will get called from {@link ElementsService::buildElementsQuery()} as it is building out a database
	 * query to fetch elements with a given criteria. It will only be called if {@link hasContent()} returns `true`.
	 *
	 * If this method returns `false`, no content table will be joined in, and it will be up to the elements’
	 * {@link BaseElementModel::getContent() getContent()} methods to fetch their content rows on demand.
	 *
	 * @param ElementCriteriaModel The element criteria.
	 *
	 * @return string|false The content table name, or `false` if it cannot be determined.
	 */
	public function getContentTableForElementsQuery(ElementCriteriaModel $criteria);

	/**
	 * Returns the fields that should take part in an upcoming elements qurery.
	 *
	 * These fields will get their own parameters in the {@link ElementCriteriaModel} that gets passed in,
	 * their field types will each have an opportunity to help build the element query, and their columns in the content
	 * table will be selected by the query (for those that have one).
	 *
	 * If a field has its own column in the content table, but the column name is prefixed with something besides
	 * “field_”, make sure you set the `columnPrefix` attribute on the {@link FieldModel}, so
	 * {@link ElementsService::buildElementsQuery()} knows which column to select.
	 *
	 * @param ElementCriteriaModel
	 *
	 * @return FieldModel[]
	 */
	public function getFieldsForElementsQuery(ElementCriteriaModel $criteria);

	/**
	 * Returns the field column names that should be selected from the content table.
	 *
	 * This method will tell {@link ElementsService::buildElementsQuery()} which custom fields it should be selecting
	 * from the {@link getContentTableForElementsQuery() content table}, as well as the custom field handle that the
	 * column corresponds to.
	 *
	 * @param ElementCriteriaModel
	 *
	 * @deprecated Deprecated in 2.3. Element types should implement {@link getFieldsForElementsQuery()} instead.
	 * @return array
	 */
	public function getContentFieldColumnsForElementsQuery(ElementCriteriaModel $criteria);

	/**
	 * Returns the element query condition for a custom status criteria.
	 *
	 * If the ElementCriteriaModel’s {@link ElementCriteriaModel::status status} parameter is set to something besides
	 * 'enabled' or 'disabled', and it’s one of the statuses that you’ve defined in {@link getStatuses()}, this method
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
	 * @param DbCommand $query  The database query.
	 * @param string    $status The custom status.
	 *
	 * @return string|false
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status);

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * If your element type is storing additional data in its own table, this method is the place to join that table in.
	 *
	 * ```php
	 * $query
	 *     ->addSelect('mytable.foo, mytable.bar')
	 *     ->join('mytable mytable', 'mytable.id = elements.id');
	 * ```
	 *
	 * This is also where you get to check the {@link ElementCriteriaModel} for all the custom attributes that this
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
	 * @param DbCommand            $query    The database query currently being built to find the elements.
	 * @param ElementCriteriaModel $criteria The criteria that is being used to find the elements.
	 *
	 * @return null|false `false` in the event that the method is sure that no elements are going to be found.
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria);

	/**
	 * Populates an element model based on a query result.
	 *
	 * This method is called by {@link ElementsService::findElements()} after it has finished fetching all of the
	 * matching elements’ rows from the database.
	 *
	 * For each row of data returned by the query, it will call this method on the element type, and it is up to this
	 * method to take that array of raw data from the database, and populate a new element model with it.
	 *
	 * You should be able to accomplish this with a single line:
	 *
	 * ```php
	 * return MyElementTypeModel::populateModel($row);
	 * ```
	 *
	 * @param array $row The row of data in the database query result.
	 *
	 * @return BaseElementModel The element model, populated with the data in $row.
	 */
	public function populateElementModel($row);

	/**
	 * Returns an array that maps source-to-target element IDs based on the given sub-property handle.
	 *
	 * This method aids in the eager-loading of elements when performing an element query. The returned array should
	 * contain two sub-keys:
	 *
	 * - `elementType` – indicating the type of sub-elements to eager-load (the element type class handle)
	 * - `map` – an array of element ID mappings, where each element is a sub-array with `source` and `target` keys.
	 *
	 * @param BaseElementModel[] $sourceElements An array of the source elements
	 * @param string $handle     The property handle used to identify which target elements should be included in the map
	 *
	 * @return array|false The eager-loading element ID mappings, or false if no mappings exist
	 */
	public function getEagerLoadingMap($sourceElements, $handle);

	/**
	 * Returns the HTML for an editor HUD for the given element.
	 *
	 * @param BaseElementModel $element The element being edited.
	 *
	 * @return string The HTML for the editor HUD.
	 */
	public function getEditorHtml(BaseElementModel $element);

	/**
	 * Saves a given element.
	 *
	 * This method will be called when an Element Editor’s Save button is clicked. It should just wrap your service’s
	 * saveX() method.
	 *
	 * @param BaseElementModel $element The element being saved.
	 * @param array            $params  Any element params found in the POST data.
	 *
	 * @return bool Whether the element was saved successfully.
	 */
	public function saveElement(BaseElementModel $element, $params);

	/**
	 * Routes the request when the URI matches an element.
	 *
	 * @param BaseElementModel The matched element.
	 *
	 * @return mixed Can be false if no special action should be taken, a string if it should route to a template path,
	 *               or an array that can specify a controller action path, params, etc.
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element);

	/**
	 * Performs actions after an element has been moved within a structure.
	 *
	 * @param BaseElementModel $element     The element that was moved.
	 * @param int              $structureId The ID of the structure that it moved within.
	 *
	 * @return null
	 */
	public function onAfterMoveElementInStructure(BaseElementModel $element, $structureId);
}
