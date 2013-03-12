<?php
namespace Craft;

/**
 * Element type interface
 */
interface IElementType extends IComponentType
{
	/**
	 * @param BaseElementModel $element
	 * @return string|false
	 */
	public function getCpEditUriForElement(BaseElementModel $element);

	/**
	 * @param BaseElementModel
	 * @return mixed
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element);

	/**
	 * @return bool
	 */
	public function isLocalizable();

	/**
	 * @return bool
	 */
	public function isLinkable();

	/**
	 * @return array
	 */
	public function defineCustomCriteriaAttributes();

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
	 * @return BaseModel
	 */
	public function getLinkSettings();

	/**
	 * @param array $values
	 */
	public function setLinkSettings($values);

	/**
	 * @param array $settings
	 * @return array
	 */
	public function prepLinkSettings($settings);

	/**
	 * @return string|null
	 */
	public function getLinkSettingsHtml();
}
