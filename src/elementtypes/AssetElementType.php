<?php
namespace Craft;

/**
 * Asset element type
 */
class AssetElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Assets');
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
	 * Returns whether this element type has titles.
	 *
	 * @return bool
	 */
	public function hasTitles()
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
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		if (in_array($context, array('modal', 'index')))
		{
			$sourceIds = craft()->assetSources->getViewableSourceIds();
		}
		else
		{
			$sourceIds = craft()->assetSources->getAllSourceIds();
		}
		$tree = craft()->assets->getFolderTreeBySourceIds($sourceIds);

		return $this->_assembleSourceList($tree);
	}

	/**
	 * Returns a source by its key and context.
	 *
	 * @param string $key
	 * @param string|null $context
	 * @return array|null
	 */
	public function getSource($key, $context = null)
	{
		if (preg_match('/folder:(\d+)/', $key, $matches))
		{
			$folder = craft()->assets->getFolderById($matches[1]);

			if ($folder)
			{
				return $this->_assembleSourceInfoForFolder($folder, true);
			}
		}

		return parent::getSource($key, $context);
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('filename', 'extension', 'kind');
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'title'        => Craft::t('Title'),
			'filename'     => Craft::t('Filename'),
			'size'         => Craft::t('Size'),
			'dateModified' => Craft::t('Date Modified'),
		);
	}

	/**
	 * Returns the table view HTML for a given attribute.
	 *
	 * @param BaseElementModel $element
	 * @param string $attribute
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
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
					return craft()->formatter->formatSize($element->size);
				}
				else
				{
					return '';
				}
			}

			case 'dateModified':
			{
				$date = $element->$attribute;

				if ($date)
				{
					return $date->localeDate();
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
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'sourceId' => AttributeType::Number,
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
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('assetfiles.sourceId, assetfiles.folderId, assetfiles.filename, assetfiles.kind, assetfiles.width, assetfiles.height, assetfiles.size, assetfiles.dateModified')
			->join('assetfiles assetfiles', 'assetfiles.id = elements.id');

		if ($criteria->sourceId)
		{
			$query->andWhere(DbHelper::parseParam('assetfiles.sourceId', $criteria->sourceId, $query->params));
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
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return AssetFileModel::populateModel($row);
	}

	/**
	 * Returns the HTML for an editor HUD for the given element.
	 *
	 * @param BaseElementModel $element
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
	 * Transforms an asset folder tree into a source list.
	 *
	 * @access private
	 * @param array $folders
	 * @param bool  $includeNestedFolders
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
	 * @access private
	 * @param AssetFolderModel $folder
	 * @param bool $includeNestedFolders
	 * @return array
	 */
	private function _assembleSourceInfoForFolder(AssetFolderModel $folder, $includeNestedFolders = true)
	{
		$source = array(
			'label'     => ($folder->parentId ? $folder->name : Craft::t($folder->name)),
			'hasThumbs' => true,
			'criteria'  => array('folderId' => $folder->id),
		);

		if ($includeNestedFolders)
		{
			$source['nested'] = $this->_assembleSourceList($folder->getChildren(), true);
		}

		return $source;
	}

	/**
	 * Save the filename.
	 *
	 * @param BaseElementModel $element
	 * @param array $params
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params)
	{
		// No filename - no problem
		if (empty($params['filename']))
		{
			return parent::saveElement($element, $params);
		}

		// Changing the filename requires the correct kind of Model
		if (!($element instanceof AssetFileModel))
		{
			return false;
		}

		// Let's go ahead and validate the content before we do something
		if (!craft()->content->validateContent($element))
		{
			return false;
		}

		$response = craft()->assets->renameFile($element, $params['filename']);
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

		return true;
	}
}
