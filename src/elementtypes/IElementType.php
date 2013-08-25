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
	public function hasTitles();

	/**
	 * @return bool
	 */
	public function hasStatuses();

	/**
	 * @return bool
	 */
	public function isTranslatable();

	/**
	 * @return array
	 */
	public function getSources();

	/**
	 * @return array
	 */
	public function defineSearchableAttributes();

	/**
	 * @return array
	 */
	public function defineTableAttributes($source = null);

	/**
	 * @return array
	 */
	public function defineCriteriaAttributes();

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
	 * @param BaseElementModel
	 * @return mixed
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element);
}
