<?php
namespace Craft;

/**
 * The CategoryElementType class is responsible for implementing and defining categories as a native element type in
 * Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.elementtypes
 * @since     2.0
 */
class CategoryElementType extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Categories');
	}

	/**
	 * @inheritDoc IElementType::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::hasTitles()
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::isLocalized()
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::getSources()
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array();

		if ($context == 'index')
		{
			$groups = craft()->categories->getEditableGroups();
		}
		else
		{
			$groups = craft()->categories->getAllGroups();
		}

		foreach ($groups as $group)
		{
			$key = 'group:'.$group->id;

			$sources[$key] = array(
				'label'       => Craft::t($group->name),
				'data'        => array('handle' => $group->handle),
				'criteria'    => array('groupId' => $group->id),
				'structureId' => $group->structureId,
			);
		}

		return $sources;
	}

	/**
	 * @inheritDoc IElementType::getIndexHtml()
	 *
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @param array                $viewState
	 * @param string|null          $sourceKey
	 * @param string|null          $context
	 *
	 * @return string
	 */
	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context)
	{
		if ($context == 'index' && $viewState['mode'] == 'structure')
		{
			$criteria->offset = 0;
			$criteria->limit = null;

			$source = $this->getSource($sourceKey, $context);

			return craft()->templates->render('_elements/categoryindex', array(
				'viewMode'            => $viewState['mode'],
				'context'             => $context,
				'elementType'         => new ElementTypeVariable($this),
				'disabledElementIds'  => $disabledElementIds,
				'structure'           => craft()->structures->getStructureById($source['structureId']),
				'collapsedElementIds' => isset($viewState['collapsedElementIds']) ? $viewState['collapsedElementIds'] : array(),
				'elements'            => $criteria->find(),
				'groupId'             => $source['criteria']['groupId'],
			));
		}
		else
		{
			return parent::getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context);
		}
	}

	/**
	 * @inheritDoc IElementType::defineTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'title' => Craft::t('Title')
		);
	}

	/**
	 * @inheritDoc IElementType::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'group'   => AttributeType::Mixed,
			'groupId' => AttributeType::Mixed,
			'order'   => array(AttributeType::String, 'default' => 'lft'),
		);
	}

	/**
	 * @inheritDoc IElementType::modifyElementsQuery()
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('categories.groupId')
			->join('categories categories', 'categories.id = elements.id')
			->join('categorygroups categorygroups', 'categorygroups.id = categories.groupId')
			->leftJoin('structures structures', 'structures.id = categorygroups.structureId')
			->leftJoin('structureelements structureelements', array('and', 'structureelements.structureId = structures.id', 'structureelements.elementId = categories.id'));

		if ($criteria->groupId)
		{
			$query->andWhere(DbHelper::parseParam('categories.groupId', $criteria->groupId, $query->params));
		}

		if ($criteria->group)
		{
			$query->andWhere(DbHelper::parseParam('categorygroups.handle', $criteria->group, $query->params));
		}
	}

	/**
	 * @inheritDoc IElementType::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return CategoryModel::populateModel($row);
	}

	/**
	 * @inheritDoc IElementType::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'label' => Craft::t('Title'),
				'locale' => $element->locale,
				'id' => 'title',
				'name' => 'title',
				'value' => $element->getContent()->title,
				'errors' => $element->getErrors('title'),
				'first' => true,
				'autofocus' => true,
				'required' => true
			)
		));

		$html .= craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'label' => Craft::t('Slug'),
				'locale' => $element->locale,
				'id' => 'slug',
				'name' => 'slug',
				'value' => $element->slug,
				'errors' => $element->getErrors('slug'),
				'required' => true
			)
		));

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * @inheritDoc IElementType::saveElement()
	 *
	 * @param BaseElementModel $element
	 * @param array            $params
	 *
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params)
	{
		if (isset($params['slug']))
		{
			$element->slug = $params['slug'];
		}

		return craft()->categories->saveCategory($element);
	}

	/**
	 * @inheritDoc IElementType::routeRequestForMatchedElement()
	 *
	 * @param BaseElementModel
	 *
	 * @return mixed Can be false if no special action should be taken, a string if it should route to a template path,
	 *               or an array that can specify a controller action path, params, etc.
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		$group = $element->getGroup();

		// Make sure the group is set to have URLs
		if ($group->hasUrls)
		{
			return array(
				'action' => 'templates/render',
				'params' => array(
					'template' => $group->template,
					'variables' => array(
						'category' => $element
					)
				)
			);
		}

		return false;
	}

	/**
	 * @inheritDoc IElementType::onAfterMoveElementInStructure()
	 *
	 * @param BaseElementModel $element
	 * @param int              $structureId
	 *
	 * @return null
	 */
	public function onAfterMoveElementInStructure(BaseElementModel $element, $structureId)
	{
		// Was the category moved within its group's structure?
		if ($element->getGroup()->structureId == $structureId)
		{
			// Update its URI
			craft()->elements->updateElementSlugAndUri($element);

			// Make sure that each of the category's ancestors are related wherever the category is related
			$newRelationValues = array();

			$ancestorIds = $element->getAncestors()->ids();

			$sources = craft()->db->createCommand()
				->select('fieldId, sourceId, sourceLocale')
				->from('relations')
				->where('targetId = :categoryId', array(':categoryId' => $element->id))
				->queryAll();

			foreach ($sources as $source)
			{
				$existingAncestorRelations = craft()->db->createCommand()
					->select('targetId')
					->from('relations')
					->where(array('and', 'fieldId = :fieldId', 'sourceId = :sourceId', 'sourceLocale = :sourceLocale', array('in', 'targetId', $ancestorIds)), array(
						':fieldId'      => $source['fieldId'],
						':sourceId'     => $source['sourceId'],
						':sourceLocale' => $source['sourceLocale']
					))
					->queryColumn();

				$missingAncestorRelations = array_diff($ancestorIds, $existingAncestorRelations);

				foreach ($missingAncestorRelations as $categoryId)
				{
					$newRelationValues[] = array($source['fieldId'], $source['sourceId'], $source['sourceLocale'], $categoryId);
				}
			}

			if ($newRelationValues)
			{
				craft()->db->createCommand()->insertAll('relations', array('fieldId', 'sourceId', 'sourceLocale', 'targetId'), $newRelationValues);
			}
		}
	}
}
