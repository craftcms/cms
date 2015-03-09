<?php
namespace Craft;

/**
 * The AssetElementType class is responsible for implementing and defining assets as a native element type in Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.elementtypes
 * @since     1.0
 */
class AssetElementType extends BaseElementType
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
		return Craft::t('Assets');
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
	 * @inheritDoc IElementType::getSources()
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		if ($context == 'index')
		{
			$sourceIds = craft()->assetSources->getViewableSourceIds();
		}
		else
		{
			$sourceIds = craft()->assetSources->getAllSourceIds();
		}

		$tree = craft()->assets->getFolderTreeBySourceIds($sourceIds);
		$sources = $this->_assembleSourceList($tree);

		// Allow plugins to modify the sources
		craft()->plugins->call('modifyAssetSources', array(&$sources, $context));

		return $sources;
	}

	/**
	 * @inheritDoc IElementType::getSource()
	 *
	 * @param string      $key
	 * @param string|null $context
	 *
	 * @return array|null
	 */
	public function getSource($key, $context = null)
	{
		if (preg_match('/folder:(\d+)(:single)?/', $key, $matches))
		{
			$folder = craft()->assets->getFolderById($matches[1]);

			if ($folder)
			{
				return $this->_assembleSourceInfoForFolder($folder, empty($matches[2]));
			}
		}

		return parent::getSource($key, $context);
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
		$actions = array();

		if (preg_match('/^folder:(\d+)$/', $source, $matches))
		{
			$folderId = $matches[1];

			// View
			$viewAction = craft()->elements->getAction('View');
			$viewAction->setParams(array(
				'label' => Craft::t('View asset'),
			));
			$actions[] = $viewAction;

			// Edit
			$editAction = craft()->elements->getAction('Edit');
			$editAction->setParams(array(
				'label' => Craft::t('Edit asset'),
			));
			$actions[] = $editAction;

			// Rename File
			if (
				craft()->assets->canUserPerformAction($folderId, 'removeFromAssetSource') &&
				craft()->assets->canUserPerformAction($folderId, 'uploadToAssetSource')
			)
			{
				$actions[] = 'RenameFile';
			}

			// Replace File
			if (craft()->assets->canUserPerformAction($folderId, 'uploadToAssetSource'))
			{
				$actions[] = 'ReplaceFile';
			}

			// Copy Reference Tag
			$copyRefTagAction = craft()->elements->getAction('CopyReferenceTag');
			$copyRefTagAction->setParams(array(
				'elementType' => 'asset',
			));
			$actions[] = $copyRefTagAction;

			// Delete
			if (craft()->assets->canUserPerformAction($folderId, 'removeFromAssetSource'))
			{
				$actions[] = 'DeleteAssets';
			}
		}

		// Allow plugins to add additional actions
		$allPluginActions = craft()->plugins->call('addAssetActions', array($source), true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc IElementType::defineSearchableAttributes()
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('filename', 'extension', 'kind');
	}

	/**
	 * @inheritDoc IElementType::defineSortableAttributes()
	 *
	 * @retrun array
	 */
	public function defineSortableAttributes()
	{
		$attributes = array(
			'title'        => Craft::t('Title'),
			'filename'     => Craft::t('Filename'),
			'size'         => Craft::t('Size'),
			'dateModified' => Craft::t('Date Modified'),
		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyAssetSortableAttributes', array(&$attributes));

		return $attributes;
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
		$attributes = array(
			'title'        => Craft::t('Title'),
			'filename'     => Craft::t('Filename'),
			'size'         => Craft::t('Size'),
			'dateModified' => Craft::t('Date Modified'),
		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyAssetTableAttributes', array(&$attributes, $source));

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
		$pluginAttributeHtml = craft()->plugins->callFirst('getAssetTableAttributeHtml', array($element, $attribute), true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'filename':
			{
				return HtmlHelper::encodeParams('<span style="word-break: break-word;">{fileName}</span>', array('fileName' => $element->filename));
			}

			case 'size':
			{
				if ($element->size)
				{
					return craft()->formatter->formatSize($element->size);
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
	 * @inheritDoc IElementType::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'sourceId' => AttributeType::Number,
			'source'   => AttributeType::Handle,
			'folderId' => AttributeType::Number,
			'filename' => AttributeType::String,
			'kind'     => AttributeType::Mixed,
			'width'    => AttributeType::Number,
			'height'   => AttributeType::Number,
			'size'     => AttributeType::Number,
			'order'    => array(AttributeType::String, 'default' => 'title asc'),
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
			->addSelect('assetfiles.sourceId, assetfiles.folderId, assetfiles.filename, assetfiles.kind, assetfiles.width, assetfiles.height, assetfiles.size, assetfiles.dateModified')
			->join('assetfiles assetfiles', 'assetfiles.id = elements.id');

		if (!empty($criteria->source))
		{
			$query->join('assetsources assetsources', 'assetfiles.sourceId = assetsources.id');
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
				$query->andWhere(DbHelper::parseParam('assetfiles.kind', array_merge(array('or'), $criteria->kind), $query->params));
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
	 * @inheritDoc IElementType::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return AssetFileModel::populateModel($row);
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
				'label'     => Craft::t('Filename'),
				'id'        => 'filename',
				'name'      => 'filename',
				'value'     => $element->filename,
				'errors'    => $element->getErrors('filename'),
				'first'     => true,
				'required'  => true
			)
		));

		$html .= craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'label'     => Craft::t('Title'),
				'locale'    => $element->locale,
				'id'        => 'title',
				'name'      => 'title',
				'value'     => $element->title,
				'errors'    => $element->getErrors('title'),
				'required'  => true
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
		// Is the filename changing?
		if (!empty($params['filename']) && $params['filename'] != $element->filename)
		{
			// Validate the content before we do anything drastic
			if (!craft()->content->validateContent($element))
			{
				return false;
			}

			$oldFilename = $element->filename;
			$newFilename = $params['filename'];

			// Rename the file
			$response = craft()->assets->renameFile($element, $newFilename);

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
			craft()->assets->renameFile($element, $oldFilename);
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
	private function _assembleSourceList($folders, $includeNestedFolders = true)
	{
		$sources = array();

		foreach ($folders as $folder)
		{
			$sources['folder:'.$folder->id] = $this->_assembleSourceInfoForFolder($folder, $includeNestedFolders);
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
	private function _assembleSourceInfoForFolder(AssetFolderModel $folder, $includeNestedFolders = true)
	{
		$source = array(
			'label'     => ($folder->parentId ? $folder->name : Craft::t($folder->name)),
			'hasThumbs' => true,
			'criteria'  => array('folderId' => $folder->id),
			'data'      => array('upload' => is_null($folder->sourceId) ? true : craft()->assets->canUserPerformAction($folder->id, 'uploadToAssetSource'))
		);

		if ($includeNestedFolders)
		{
			$source['nested'] = $this->_assembleSourceList($folder->getChildren(), true);
		}

		return $source;
	}
}
