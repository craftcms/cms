<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\db\Query;
use craft\app\enums\AttributeType;
use craft\app\helpers\DbHelper;
use craft\app\models\AssetFile as AssetFileModel;
use craft\app\models\AssetFolder as AssetFolderModel;
use craft\app\models\BaseElementModel;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * The Asset class is responsible for implementing and defining assets as a native element type in Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Asset extends Element
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public static function getName()
	{
		return Craft::t('app', 'Assets');
	}

	/**
	 * @inheritDoc ElementInterface::hasContent()
	 *
	 * @return bool
	 */
	public static function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::hasTitles()
	 *
	 * @return bool
	 */
	public static function hasTitles()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::isLocalized()
	 *
	 * @return bool
	 */
	public static function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::getSources()
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public static function getSources($context = null)
	{
		if ($context == 'index')
		{
			$sourceIds = Craft::$app->assetSources->getViewableSourceIds();
		}
		else
		{
			$sourceIds = Craft::$app->assetSources->getAllSourceIds();
		}

		$tree = Craft::$app->assets->getFolderTreeBySourceIds($sourceIds);

		return static::_assembleSourceList($tree);
	}

	/**
	 * @inheritDoc ElementInterface::getSource()
	 *
	 * @param string      $key
	 * @param string|null $context
	 *
	 * @return array|null
	 */
	public static function getSource($key, $context = null)
	{
		if (preg_match('/folder:(\d+)(:single)?/', $key, $matches))
		{
			$folder = Craft::$app->assets->getFolderById($matches[1]);

			if ($folder)
			{
				return static::_assembleSourceInfoForFolder($folder, empty($matches[2]));
			}
		}

		return parent::getSource($key, $context);
	}

	/**
	 * @inheritDoc ElementInterface::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public static function getAvailableActions($source = null)
	{
		if (!preg_match('/^folder:(\d+)$/', $source, $matches))
		{
			return;
		}

		$folderId = $matches[1];

		$actions = [];

		// View
		$viewAction = Craft::$app->elements->getAction('View');
		$viewAction->setParams([
			'label' => Craft::t('app', 'View asset'),
		]);
		$actions[] = $viewAction;

		// Edit
		$editAction = Craft::$app->elements->getAction('Edit');
		$editAction->setParams([
			'label' => Craft::t('app', 'Edit asset'),
		]);
		$actions[] = $editAction;

		// Rename File
		if (
			Craft::$app->assets->canUserPerformAction($folderId, 'removeFromAssetSource') &&
			Craft::$app->assets->canUserPerformAction($folderId, 'uploadToAssetSource')
		)
		{
			$actions[] = 'RenameFile';
		}

		// Replace File
		if (Craft::$app->assets->canUserPerformAction($folderId, 'uploadToAssetSource'))
		{
			$actions[] = 'ReplaceFile';
		}

		// Copy Reference Tag
		$copyRefTagAction = Craft::$app->elements->getAction('CopyReferenceTag');
		$copyRefTagAction->setParams([
			'elementType' => 'asset',
		]);
		$actions[] = $copyRefTagAction;

		// Delete
		if (Craft::$app->assets->canUserPerformAction($folderId, 'removeFromAssetSource'))
		{
			$actions[] = 'DeleteAssets';
		}

		// Allow plugins to add additional actions
		$allPluginActions = Craft::$app->plugins->call('addAssetActions', [$source], true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc ElementInterface::defineSearchableAttributes()
	 *
	 * @return array
	 */
	public static function defineSearchableAttributes()
	{
		return ['filename', 'extension', 'kind'];
	}

	/**
	 * @inheritDoc ElementInterface::defineSortableAttributes()
	 *
	 * @retrun array
	 */
	public static function defineSortableAttributes()
	{
		$attributes = [
			'title'        => Craft::t('app', 'Title'),
			'filename'     => Craft::t('app', 'Filename'),
			'size'         => Craft::t('app', 'Size'),
			'dateModified' => Craft::t('app', 'Date Modified'),
		];

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyAssetSortableAttributes', [&$attributes]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementInterface::defineTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public static function defineTableAttributes($source = null)
	{
		$attributes = [
			'title'        => Craft::t('app', 'Title'),
			'filename'     => Craft::t('app', 'Filename'),
			'size'         => Craft::t('app', 'Size'),
			'dateModified' => Craft::t('app', 'Date Modified'),
		];

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyAssetTableAttributes', [&$attributes, $source]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementInterface::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public static function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		// First give plugins a chance to set this
		$pluginAttributeHtml = Craft::$app->plugins->callFirst('getAssetTableAttributeHtml', [$element, $attribute], true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'filename':
			{
				return '<span style="word-break: break-word;">'.$element->filename.'</span>';
			}

			case 'size':
			{
				if ($element->size)
				{
					return Craft::$app->getFormatter()->formatSize($element->size);
				}
				else
				{
					return '';
				}
			}

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * @inheritDoc ElementInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public static function defineCriteriaAttributes()
	{
		return [
			'sourceId' => AttributeType::Number,
			'source'   => AttributeType::Handle,
			'folderId' => AttributeType::Number,
			'filename' => AttributeType::String,
			'kind'     => AttributeType::Mixed,
			'width'    => AttributeType::Number,
			'height'   => AttributeType::Number,
			'size'     => AttributeType::Number,
			'order'    => [AttributeType::String, 'default' => 'title asc'],
		];
	}

	/**
	 * @inheritDoc ElementInterface::modifyElementsQuery()
	 *
	 * @param Query                $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public static function modifyElementsQuery(Query $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('assetfiles.sourceId, assetfiles.folderId, assetfiles.filename, assetfiles.kind, assetfiles.width, assetfiles.height, assetfiles.size, assetfiles.dateModified')
			->innerJoin('{{%assetfiles}} assetfiles', 'assetfiles.id = elements.id');

		if (!empty($criteria->source))
		{
			$query->innerJoin('{{%assetsources}} assetsources', 'assetfiles.sourceId = assetsources.id');
		}

		if ($criteria->sourceId)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.sourceId', $criteria->sourceId, $query->params));
		}

		if ($criteria->source)
		{
			$query->andWhere(DbHelper::parseParam('assetsources.handle', $criteria->source, $query->params));
		}

		if ($criteria->folderId)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.folderId', $criteria->folderId, $query->params));
		}

		if ($criteria->filename)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.filename', $criteria->filename, $query->params));
		}

		if ($criteria->kind)
		{
			if (is_array($criteria->kind))
			{
				$query->andWhere(DbHelper::parseParam('assetfiles.kind', array_merge(['or'], $criteria->kind), $query->params));
			}
			else
			{
				$query->andWhere(DbHelper::parseParam('assetfiles.kind', $criteria->kind, $query->params));
			}

		}

		if ($criteria->width)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.width', $criteria->width, $query->params));
		}

		if ($criteria->height)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.height', $criteria->height, $query->params));
		}

		if ($criteria->size)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.size', $criteria->size, $query->params));
		}
	}

	/**
	 * @inheritDoc ElementInterface::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public static function populateElementModel($row)
	{
		return AssetFileModel::populateModel($row);
	}

	/**
	 * @inheritDoc ElementInterface::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public static function getEditorHtml(BaseElementModel $element)
	{
		$html = Craft::$app->templates->renderMacro('_includes/forms', 'textField', [
			[
				'label'     => Craft::t('app', 'Filename'),
				'id'        => 'filename',
				'name'      => 'filename',
				'value'     => $element->filename,
				'errors'    => $element->getErrors('filename'),
				'first'     => true,
				'required'  => true
			]
		]);

		$html .= Craft::$app->templates->renderMacro('_includes/forms', 'textField', [
			[
				'label'     => Craft::t('app', 'Title'),
				'locale'    => $element->locale,
				'id'        => 'title',
				'name'      => 'title',
				'value'     => $element->title,
				'errors'    => $element->getErrors('title'),
				'required'  => true
			]
		]);

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * @inheritDoc ElementInterface::saveElement()
	 *
	 * @param BaseElementModel $element
	 * @param array            $params
	 *
	 * @return bool
	 */
	public static function saveElement(BaseElementModel $element, $params)
	{
		// Is the filename changing?
		if (!empty($params['filename']) && $params['filename'] != $element->filename)
		{
			// Validate the content before we do anything drastic
			if (!Craft::$app->content->validateContent($element))
			{
				return false;
			}

			$oldFilename = $element->filename;
			$newFilename = $params['filename'];

			// Rename the file
			$response = Craft::$app->assets->renameFile($element, $newFilename);

			// Did it work?
			if ($response->isConflict())
			{
				$element->addError('filename', $response->getDataItem('prompt')->message);
				return false;
			}

			if ($response->isError())
			{
				$element->addError('filename', $response->errorMessage);
				return false;
			}
		}
		else
		{
			$newFilename = null;
		}

		$success = parent::saveElement($element, $params);

		if (!$success && $newFilename)
		{
			// Better rename it back
			Craft::$app->assets->renameFile($element, $oldFilename);
		}

		return $success;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Transforms an asset folder tree into a source list.
	 *
	 * @param array $folders
	 * @param bool  $includeNestedFolders
	 *
	 * @return array
	 */
	private static function _assembleSourceList($folders, $includeNestedFolders = true)
	{
		$sources = [];

		foreach ($folders as $folder)
		{
			$sources['folder:'.$folder->id] = static::_assembleSourceInfoForFolder($folder, $includeNestedFolders);
		}

		return $sources;
	}

	/**
	 * Transforms an AssetFolderModel into a source info array.
	 *
	 * @param AssetFolderModel $folder
	 * @param bool             $includeNestedFolders
	 *
	 * @return array
	 */
	private static function _assembleSourceInfoForFolder(AssetFolderModel $folder, $includeNestedFolders = true)
	{
		$source = [
			'label'     => ($folder->parentId ? $folder->name : Craft::t('app', $folder->name)),
			'hasThumbs' => true,
			'criteria'  => ['folderId' => $folder->id],
			'data'      => ['upload' => is_null($folder->sourceId) ? true : Craft::$app->assets->canUserPerformAction($folder->id, 'uploadToAssetSource')]
		];

		if ($includeNestedFolders)
		{
			$source['nested'] = static::_assembleSourceList($folder->getChildren(), true);
		}

		return $source;
	}
}
