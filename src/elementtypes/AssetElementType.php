<?php
namespace Craft;

/**
 * The AssetElementType class is responsible for implementing and defining assets as a native element type in Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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

		if ($context == 'settings')
		{
			$additionalCriteria = array('parentId' => ':empty:');
		}
		else
		{
			$additionalCriteria = array();
		}

		$tree = craft()->assets->getFolderTreeBySourceIds($sourceIds, $additionalCriteria);
		$sources = $this->_assembleSourceList($tree, $context != 'settings');

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

			// Download
			$actions[] = 'DownloadFile';

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
	 * @return array
	 */
	public function defineSortableAttributes()
	{
		$attributes = array(
			'title'        => Craft::t('Title'),
			'filename'     => Craft::t('Filename'),
			'size'         => Craft::t('File Size'),
			'dateModified' => Craft::t('File Modification Date'),
			'dateCreated'  => Craft::t('Date Uploaded'),
			'dateUpdated'  => Craft::t('Date Updated'),
		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyAssetSortableAttributes', array(&$attributes));

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
			'title'        => array('label' => Craft::t('Title')),
			'filename'     => array('label' => Craft::t('Filename')),
			'size'         => array('label' => Craft::t('File Size')),
			'kind'         => array('label' => Craft::t('File Kind')),
			'imageSize'    => array('label' => Craft::t('Image Size')),
			'width'        => array('label' => Craft::t('Image Width')),
			'height'       => array('label' => Craft::t('Image Height')),
			'id'           => array('label' => Craft::t('ID')),
			'dateModified' => array('label' => Craft::t('File Modified Date')),
			'dateCreated'  => array('label' => Craft::t('Date Created')),
			'dateUpdated'  => array('label' => Craft::t('Date Updated')),
		);

		// Allow plugins to modify the attributes
		$pluginAttributes = craft()->plugins->call('defineAdditionalAssetTableAttributes', array(), true);

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
		return array('filename', 'size', 'dateModified');
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

			case 'kind':
			{
				return IOHelper::getFileKindLabel($element->kind);
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

			case 'imageSize':
			{
				if (($width = $element->getWidth()) && ($height = $element->getHeight()))
				{
					return "{$width} × {$height}";
				}
				else
				{
					return '';
				}
			}

			case 'width':
			case 'height':
			{
				$size = $element->$attribute;

				return ($size ? $size.'px' : '');
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
			'filename'          => AttributeType::String,
			'folderId'          => AttributeType::Number,
			'height'            => AttributeType::Number,
			'includeSubfolders' => AttributeType::Bool,
			'kind'              => AttributeType::Mixed,
			'order'             => array(AttributeType::String, 'default' => 'title asc'),
			'size'              => AttributeType::Number,
			'source'            => AttributeType::Handle,
			'sourceId'          => AttributeType::Number,
			'width'             => AttributeType::Number,
			'withTransforms'    => AttributeType::Mixed,
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
			->addSelect('assetfiles.sourceId, assetfiles.folderId, assetfiles.filename, assetfiles.kind, assetfiles.width, assetfiles.height, assetfiles.size, assetfiles.dateModified, assetfolders.path as folderPath')
			->join('assetfiles assetfiles', 'assetfiles.id = elements.id')
			->join('assetfolders assetfolders', 'assetfolders.id = assetfiles.folderId');

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
			if ($criteria->includeSubfolders)
			{
				$folders = craft()->assets->getAllDescendantFolders(craft()->assets->getFolderById($criteria->folderId));
				$query->andWhere(DbHelper::parseParam('assetfiles.folderId', array_keys($folders), $query->params));
			}
			else
			{
				$query->andWhere(DbHelper::parseParam('assetfiles.folderId', $criteria->folderId, $query->params));
			}
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

		// Clear out existing onPopulateElements handlers
		$criteria->detachEventHandler('onPopulateElements', array($this, 'eagerLoadTransforms'));

		// Are we eager-loading any transforms?
		if ($criteria->withTransforms)
		{
			$criteria->attachEventHandler('onPopulateElements', array($this, 'eagerLoadTransforms'));
		}
	}

	/**
	 * Eager-loads image transforms requested by an element criteria model.
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function eagerLoadTransforms(Event $event)
	{
		/** @var ElementCriteriaModel $criteria */
		$criteria = $event->sender;
		$transforms = ArrayHelper::stringToArray($criteria->withTransforms);

		craft()->assetTransforms->eagerLoadTransforms($event->params['elements'], $transforms);
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
				'required'  => true,
				'class'     => 'renameHelper'
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementType::getTableAttributesForSource()
	 *
	 * @param string $sourceKey
	 *
	 * @return array
	 */
	protected function getTableAttributesForSource($sourceKey)
	{
		// Make sure it's a folder
		if (strncmp($sourceKey, 'folder:', 7) === 0)
		{
			$folder = craft()->assets->getFolderById(substr($sourceKey, 7));

			// Is it a nested folder?
			if ($folder && $folder->parentId)
			{
				// Get the root folder in that source
				$rootFolder = craft()->assets->getRootFolderBySourceId($folder->sourceId);

				if ($rootFolder)
				{
					// Use the root folder's source key
					$sourceKey = 'folder:'.$rootFolder->id;
				}
			}
		}

		return parent::getTableAttributesForSource($sourceKey);
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
			'label'     => $folder->parentId ? $folder->name : Craft::t($folder->name),
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
