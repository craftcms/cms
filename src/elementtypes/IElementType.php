<?php
namespace Craft;

/**
 * Element type interface
 */
interface IElementType extends IComponentType
{
	/**
	 * @return bool
	 */
	public function hasContent();

	/**
	 * @return bool
	 */
	public function hasTitles();

	/**
	 * @return bool
	 */
	public function isLocalized();

	/**
	 * @return bool
	 */
	public function hasStatuses();

	/**
	 * @return array|null
	 */
	public function getStatuses();

	/**
	 * @param string|null $context
	 * @return array
	 */
	public function getSources($context = null);

	/**
	 * @return array
	 */
	public function defineSearchableAttributes();

	/**
	 * @return array
	 */
	public function defineTableAttributes($source = null);

	/**
	 * @param BaseElementModel $element
	 * @param string $attribute
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute);

	/**
	 * @return array
	 */
	public function defineCriteriaAttributes();

	/**
	 * @param ElementCriteriaModel $criteria
	 * @return string
	 */
	public function getContentTableForElementsQuery(ElementCriteriaModel $criteria);

	/**
	 * @param ElementCriteriaModel
	 * @return array
	 */
	public function getContentFieldColumnsForElementsQuery(ElementCriteriaModel $criteria);

	/**
	 * @param DbCommand $query
	 * @param string $status
	 * @return string|false
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status);

	/**
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return null|false
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria);

	/**
	 * @param array $row
	 * @return BaseModel
	 */
	public function populateElementModel($row);

	/**
	 * @param BaseElementModel $element
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element);

	/**
	 * @param BaseElementModel $element
	 * @param array $params
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params);

	/**
	 * @param BaseElementModel
	 * @return mixed
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element);
}
