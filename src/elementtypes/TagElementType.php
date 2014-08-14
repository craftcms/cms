<?php
namespace Craft;

/**
 * The TagElementType class is responsible for implementing and definingtags as a native element type in Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.elementtypes
 * @since     1.1
 */
class TagElementType extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Tags');
	}

	/**
	 * Returns whether this element type has content.
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * Returns whether this element type stores data on a per-locale basis.
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array();

		foreach (craft()->tags->getAllTagGroups() as $tagGroup)
		{
			$key = 'taggroup:'.$tagGroup->id;

			$sources[$key] = array(
				'label'    => Craft::t($tagGroup->name),
				'criteria' => array('groupId' => $tagGroup->id)
			);
		}

		return $sources;
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('name');
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'name' => Craft::t('Name'),
		);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'name'    => AttributeType::String,
			'group'   => AttributeType::Mixed,
			'groupId' => AttributeType::Mixed,
			'order'   => array(AttributeType::String, 'default' => 'tags.name asc'),

			// TODO: Deprecated
			'set'     => AttributeType::Mixed,
			'setId'   => AttributeType::Mixed,
		);
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('tags.groupId, tags.name')
			->join('tags tags', 'tags.id = elements.id');

		if ($criteria->name)
		{
			$query->andWhere(DbHelper::parseParam('tags.name', $criteria->name, $query->params));
		}


		// Still support the deprecated params
		if ($criteria->setId && !$criteria->groupId)
		{
			craft()->deprecator->log('TagElementType::modifyElementsQuery():setId_param', 'The ‘setId’ tag param has been deprecated. Use ‘groupId’ instead.');
			$criteria->groupId = $criteria->setId;
			$criteria->setId = null;
		}

		if ($criteria->set && !$criteria->group)
		{
			craft()->deprecator->log('TagElementType::modifyElementsQuery():set_param', 'The ‘set’ tag param has been deprecated. Use ‘group’ instead.');
			$criteria->group = $criteria->set;
			$criteria->set = null;
		}

		if ($criteria->groupId)
		{
			$query->andWhere(DbHelper::parseParam('tags.groupId', $criteria->groupId, $query->params));
		}

		if ($criteria->group)
		{
			$query->join('taggroups taggroups', 'taggroups.id = tags.groupId');
			$query->andWhere(DbHelper::parseParam('taggroups.handle', $criteria->group, $query->params));
		}
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return TagModel::populateModel($row);
	}

	/**
	 * Returns the HTML for an editor HUD for the given element.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'label'     => Craft::t('Name'),
				'id'        => 'name',
				'name'      => 'name',
				'value'     => $element->name,
				'errors'    => $element->getErrors('name'),
				'first'     => true,
				'autofocus' => true,
				'required'  => true
			)
		));

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * Saves a given element.
	 *
	 * @param BaseElementModel $element
	 * @param array            $params
	 *
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params)
	{
		if (isset($params['name']))
		{
			$element->name = $params['name'];
		}

		return craft()->tags->saveTag($element);
	}
}
