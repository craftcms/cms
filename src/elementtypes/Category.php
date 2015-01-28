<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementtypes;

use Craft;
use craft\app\db\Command;
use craft\app\db\Query;
use craft\app\enums\AttributeType;
use craft\app\helpers\DbHelper;
use craft\app\models\BaseElementModel;
use craft\app\models\Category as CategoryModel;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * The Category class is responsible for implementing and defining categories as a native element type in
 * Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Category extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Categories');
	}

	/**
	 * @inheritDoc ElementTypeInterface::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::hasTitles()
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::isLocalized()
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::getSources()
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = [];

		if ($context == 'index')
		{
			$groups = Craft::$app->categories->getEditableGroups();
		}
		else
		{
			$groups = Craft::$app->categories->getAllGroups();
		}

		foreach ($groups as $group)
		{
			$key = 'group:'.$group->id;

			$sources[$key] = [
				'label'             => Craft::t('app', $group->name),
				'data'              => ['handle' => $group->handle],
				'criteria'          => ['groupId' => $group->id],
				'structureId'       => $group->structureId,
				'structureEditable' => Craft::$app->getUser()->checkPermission('editCategories:'.$group->id),
			];
		}

		return $sources;
	}

	/**
	 * @inheritDoc ElementTypeInterface::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		if (preg_match('/^group:(\d+)$/', $source, $matches))
		{
			$group = Craft::$app->categories->getGroupById($matches[1]);
		}

		if (empty($group))
		{
			return;
		}

		$actions = [];

		// Set Status
		$actions[] = 'SetStatus';

		if ($group->hasUrls)
		{
			// View
			$viewAction = Craft::$app->elements->getAction('View');
			$viewAction->setParams([
				'label' => Craft::t('app', 'View category'),
			]);
			$actions[] = $viewAction;
		}

		// Edit
		$editAction = Craft::$app->elements->getAction('Edit');
		$editAction->setParams([
			'label' => Craft::t('app', 'Edit category'),
		]);
		$actions[] = $editAction;

		// New Child
		$structure = Craft::$app->structures->getStructureById($group->structureId);

		if ($structure)
		{
			$newChildAction = Craft::$app->elements->getAction('NewChild');
			$newChildAction->setParams([
				'label'       => Craft::t('app', 'Create a new child category'),
				'maxLevels'   => $structure->maxLevels,
				'newChildUrl' => 'categories/'.$group->handle.'/new',
			]);
			$actions[] = $newChildAction;
		}

		// Delete
		$deleteAction = Craft::$app->elements->getAction('Delete');
		$deleteAction->setParams([
			'confirmationMessage' => Craft::t('app', 'Are you sure you want to delete the selected categories?'),
			'successMessage'      => Craft::t('app', 'Categories deleted.'),
		]);
		$actions[] = $deleteAction;

		// Allow plugins to add additional actions
		$allPluginActions = Craft::$app->plugins->call('addCategoryActions', [$source], true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineSortableAttributes()
	 *
	 * @retrun array
	 */
	public function defineSortableAttributes()
	{
		$attributes = [
			'title' => Craft::t('app', 'Title'),
			'uri'   => Craft::t('app', 'URI'),
		];

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyCategorySortableAttributes', [&$attributes]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		$attributes = [
			'title' => Craft::t('app', 'Title'),
			'uri'   => Craft::t('app', 'URI'),
		];

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyCategoryTableAttributes', [&$attributes, $source]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementTypeInterface::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		// First give plugins a chance to set this
		$pluginAttributeHtml = Craft::$app->plugins->callFirst('getCategoryTableAttributeHtml', [$element, $attribute], true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		return parent::getTableAttributeHtml($element, $attribute);
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return [
			'group'   => AttributeType::Mixed,
			'groupId' => AttributeType::Mixed,
			'order'   => [AttributeType::String, 'default' => 'lft'],
		];
	}

	/**
	 * @inheritDoc ElementTypeInterface::modifyElementsQuery()
	 *
	 * @param Command            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(Command $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('categories.groupId')
			->innerJoin('{{%categories}} categories', 'categories.id = elements.id')
			->innerJoin('{{%categorygroups}} categorygroups', 'categorygroups.id = categories.groupId')
			->leftJoin('{{%structures}} structures', 'structures.id = categorygroups.structureId')
			->leftJoin('{{%structureelements}} structureelements', ['and', 'structureelements.structureId = structures.id', 'structureelements.elementId = categories.id']);

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
	 * @inheritDoc ElementTypeInterface::populateElementModel()
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
	 * @inheritDoc ElementTypeInterface::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = Craft::$app->templates->renderMacro('_includes/forms', 'textField', [
			[
				'label' => Craft::t('app', 'Title'),
				'locale' => $element->locale,
				'id' => 'title',
				'name' => 'title',
				'value' => $element->getContent()->title,
				'errors' => $element->getErrors('title'),
				'first' => true,
				'autofocus' => true,
				'required' => true
			]
		]);

		$html .= Craft::$app->templates->renderMacro('_includes/forms', 'textField', [
			[
				'label' => Craft::t('app', 'Slug'),
				'locale' => $element->locale,
				'id' => 'slug',
				'name' => 'slug',
				'value' => $element->slug,
				'errors' => $element->getErrors('slug'),
				'required' => true
			]
		]);

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * @inheritDoc ElementTypeInterface::saveElement()
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

		return Craft::$app->categories->saveCategory($element);
	}

	/**
	 * @inheritDoc ElementTypeInterface::getElementRoute()
	 *
	 * @param BaseElementModel
	 *
	 * @return mixed Can be false if no special action should be taken, a string if it should route to a template path,
	 *               or an array that can specify a controller action path, params, etc.
	 */
	public function getElementRoute(BaseElementModel $element)
	{
		$group = $element->getGroup();

		// Make sure the group is set to have URLs
		if ($group->hasUrls)
		{
			return ['templates/render', [
				'template' => $group->template,
				'variables' => [
					'category' => $element
				]
			]];
		}

		return false;
	}

	/**
	 * @inheritDoc ElementTypeInterface::onAfterMoveElementInStructure()
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
			Craft::$app->elements->updateElementSlugAndUri($element);

			// Make sure that each of the category's ancestors are related wherever the category is related
			$newRelationValues = [];

			$ancestorIds = $element->getAncestors()->ids();

			$sources = (new Query())
				->select(['fieldId', 'sourceId', 'sourceLocale'])
				->from('{{%relations}}')
				->where('targetId = :categoryId', [':categoryId' => $element->id])
				->all();

			foreach ($sources as $source)
			{
				$existingAncestorRelations = (new Query())
					->select('targetId')
					->from('{{%relations}}')
					->where(['and', 'fieldId = :fieldId', 'sourceId = :sourceId', 'sourceLocale = :sourceLocale', ['in', 'targetId', $ancestorIds]], [
						':fieldId'      => $source['fieldId'],
						':sourceId'     => $source['sourceId'],
						':sourceLocale' => $source['sourceLocale']
					])
					->column();

				$missingAncestorRelations = array_diff($ancestorIds, $existingAncestorRelations);

				foreach ($missingAncestorRelations as $categoryId)
				{
					$newRelationValues[] = [$source['fieldId'], $source['sourceId'], $source['sourceLocale'], $categoryId];
				}
			}

			if ($newRelationValues)
			{
				Craft::$app->getDb()->createCommand()->batchInsert('{{%relations}}', ['fieldId', 'sourceId', 'sourceLocale', 'targetId'], $newRelationValues)->execute();
			}
		}
	}
}
