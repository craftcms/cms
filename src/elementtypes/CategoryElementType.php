<?php
namespace Craft;

/**
 * The CategoryElementType class is responsible for implementing and defining categories as a native element type in
 * Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
				'label'             => Craft::t($group->name),
				'data'              => array('handle' => $group->handle),
				'criteria'          => array('groupId' => $group->id),
				'structureId'       => $group->structureId,
				'structureEditable' => !craft()->isConsole() ? craft()->userSession->checkPermission('editCategories:'.$group->id) : true,
			);
		}

		// Allow plugins to modify the sources
		craft()->plugins->call('modifyCategorySources', array(&$sources, $context));

		return $sources;
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		// Get the group we need to check permissions on
		if (preg_match('/^group:(\d+)$/', $source, $matches))
		{
			$group = craft()->categories->getGroupById($matches[1]);
		}

		// Now figure out what we can do with it
		$actions = array();

		if (!empty($group))
		{
			// Set Status
			$actions[] = 'SetStatus';

			if ($group->hasUrls)
			{
				// View
				$viewAction = craft()->elements->getAction('View');
				$viewAction->setParams(array(
					'label' => Craft::t('View category'),
				));
				$actions[] = $viewAction;
			}

			// Edit
			$editAction = craft()->elements->getAction('Edit');
			$editAction->setParams(array(
				'label' => Craft::t('Edit category'),
			));
			$actions[] = $editAction;

			// New Child
			$structure = craft()->structures->getStructureById($group->structureId);

			if ($structure)
			{
				$newChildAction = craft()->elements->getAction('NewChild');
				$newChildAction->setParams(array(
					'label'       => Craft::t('Create a new child category'),
					'maxLevels'   => $structure->maxLevels,
					'newChildUrl' => 'categories/'.$group->handle.'/new',
				));
				$actions[] = $newChildAction;
			}

			// Delete
			$deleteAction = craft()->elements->getAction('Delete');
			$deleteAction->setParams(array(
				'confirmationMessage' => Craft::t('Are you sure you want to delete the selected categories?'),
				'successMessage'      => Craft::t('Categories deleted.'),
			));
			$actions[] = $deleteAction;
		}

		// Allow plugins to add additional actions
		$allPluginActions = craft()->plugins->call('addCategoryActions', array($source), true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc IElementType::defineSortableAttributes()
	 *
	 * @return array
	 */
	public function defineSortableAttributes()
	{
		$attributes = array(
			'title'        => Craft::t('Title'),
			'uri'          => Craft::t('URI'),
			'dateCreated'  => Craft::t('Date Created'),
			'dateUpdated'  => Craft::t('Date Updated'),
		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyCategorySortableAttributes', array(&$attributes));

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::defineAvailableTableAttributes()
	 *
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		$attributes = array(
			'title'       => array('label' => Craft::t('Title')),
			'uri'         => array('label' => Craft::t('URI')),
			'link'        => array('label' => Craft::t('Link'), 'icon' => 'world'),
			'id'          => array('label' => Craft::t('ID')),
			'dateCreated' => array('label' => Craft::t('Date Created')),
			'dateUpdated' => array('label' => Craft::t('Date Updated')),
		);

		// Allow plugins to modify the attributes
		$pluginAttributes = craft()->plugins->call('defineAdditionalCategoryTableAttributes', array(), true);

		foreach ($pluginAttributes as $thisPluginAttributes)
		{
			$attributes = array_merge($attributes, $thisPluginAttributes);
		}

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::getDefaultTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function getDefaultTableAttributes($source = null)
	{
		$attributes = array('link');

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		// First give plugins a chance to set this
		$pluginAttributeHtml = craft()->plugins->callFirst('getCategoryTableAttributeHtml', array($element, $attribute), true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		return parent::getTableAttributeHtml($element, $attribute);
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
			craft()->elements->updateElementSlugAndUri($element, true, true, true);

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
