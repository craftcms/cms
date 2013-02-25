<?php
namespace Blocks;

/**
 * Singleton element type
 */
class SingletonElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Singletons');
	}

	/**
	 * Returns the CP edit URI for a given element.
	 *
	 * @param BaseElementModel $element
	 * @return string|null
	 */
	public function getCpEditUriForElement(BaseElementModel $element)
	{
		return 'content/singletons/'.$element->id;
	}

	/**
	 * Returns the site template path for a matched element.
	 *
	 * @param BaseElementModel
	 * @return string|false
	 */
	public function getSiteTemplateForMatchedElement(BaseElementModel $element)
	{
		return $element->template;
	}

	/**
	 * Returns the variable name the matched element should be assigned to.
	 *
	 * @return string
	 */
	public function getVariableNameForMatchedElement()
	{
		return 'singleton';
	}

	/**
	 * Returns whether this element type is linkable.
	 *
	 * @return bool
	 */
	public function isLinkable()
	{
		return true;
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCustomCriteriaAttributes()
	{
		return array(
			'name' => AttributeType::String,
		);
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('singletons.name, singletons.template, singletons.fieldLayoutId')
			->join('singletons singletons', 'singletons.id = elements.id');
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return SingletonModel::populateModel($row);
	}
}
