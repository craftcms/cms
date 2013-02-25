<?php
namespace Craft;

/**
 * Element type base class
 */
abstract class BaseElementType extends BaseComponentType implements IElementType
{
	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'ElementType';

	/**
	 * @access private
	 * @var BaseModel The model representing the current component instance's link settings.
	 */
	private $_linkSettings;

	/**
	 * Returns the CP edit URI for a given element.
	 *
	 * @param BaseElementModel $element
	 * @return string|false
	 */
	public function getCpEditUriForElement(BaseElementModel $element)
	{
		return false;
	}

	/**
	 * Returns the site template path for a matched element.
	 *
	 * @param BaseElementModel
	 * @return string|false
	 */
	public function getSiteTemplateForMatchedElement(BaseElementModel $element)
	{
		return false;
	}

	/**
	 * Returns the variable name the matched element should be assigned to.
	 *
	 * @return string
	 */
	public function getVariableNameForMatchedElement()
	{
		return 'element';
	}

	/**
	 * Returns whether this element type is localizable.
	 *
	 * @return bool
	 */
	public function isLocalizable()
	{
		return false;
	}

	/**
	 * Returns whether this element type is linkable.
	 *
	 * @return bool
	 */
	public function isLinkable()
	{
		return false;
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCustomCriteriaAttributes()
	{
		return array();
	}

	/**
	 * Returns the element query condition for a custom status criteria.
	 *
	 * @param DbCommand $query
	 * @param string $status
	 * @return string|false
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return null|false
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return BaseModel
	 */
	public function populateElementModel($row)
	{
	}

	/**
	 * Gets the link settings.
	 *
	 * @return BaseModel
	 */
	public function getLinkSettings()
	{
		if (!isset($this->_linkSettings))
		{
			$this->_linkSettings = $this->getLinkSettingsModel();
		}

		return $this->_linkSettings;
	}

	/**
	 * Sets the link setting values.
	 *
	 * @param array $values
	 */
	public function setLinkSettings($values)
	{
		if ($values)
		{
			$this->getLinkSettings()->setAttributes($values);
		}
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepLinkSettings($settings)
	{
		return $settings;
	}

	/**
	 * Returns the link settings HTML.
	 *
	 * @return string|null
	 */
	public function getLinkSettingsHtml()
	{
		return null;
	}

	/**
	 * Gets the link settings model.
	 *
	 * @access protected
	 * @return BaseModel
	 */
	protected function getLinkSettingsModel()
	{
		return new Model($this->defineCustomCriteriaAttributes());
	}
}
