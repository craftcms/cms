<?php
namespace Craft;

/**
 *
 */
class AssetTransformsService extends BaseApplicationComponent
{
	/**
	 * @var array of AssetTransformModel
	 */
	private $_assetTransforms = null;

	/**
	 * Returns all named asset transforms.
	 *
	 * @return array|null
	 */
	public function getAllTransforms()
	{
		$this->_loadAssetTransforms();

		return $this->_assetTransforms;
	}

	/**
	 * Returns an asset transform by its handle.
	 *
	 * @param $handle
	 * @return AssetTransformModel|null
	 */
	public function getTransformByHandle($handle)
	{
		$this->_loadAssetTransforms();

		if (isset($this->_assetTransforms[$handle]))
		{
			return $this->_assetTransforms[$handle];
		}

		return null;
	}


	/**
	 * Saves an asset transform.
	 *
	 * @param AssetTransformModel $transform
	 * @return bool
	 */
	public function saveTransform(AssetTransformModel $transform)
	{
		$transformRecord = $this->_getTransformRecordById($transform->id, $transform->handle);

		$transformRecord->name = $transform->name;
		$transformRecord->handle = $transform->handle;

		$heightChanged = $transformRecord->width != $transform->width || $transformRecord->height != $transform->height;
		$modeChanged = $transformRecord->mode != $transform->mode || $transformRecord->position != $transform->position;

		if ($heightChanged || $modeChanged)
		{
			$transformRecord->dimensionChangeTime = new DateTime('@'.time());
		}

		$transformRecord->mode = $transform->mode;
		$transformRecord->position = $transform->position;
		$transformRecord->width = $transform->width;
		$transformRecord->height = $transform->height;

		$recordValidates = $transformRecord->validate();

		if ($recordValidates)
		{
			$transformRecord->save(false);

			// Now that we have a transform ID, save it on the model
			if (!$transform->id)
			{
				$transform->id = $transformRecord->id;
			}

			return true;
		}
		else
		{
			$transform->addErrors($transformRecord->getErrors());
			return false;
		}
	}

	/**
	 * Deletes an asset transform by it's id.
	 *
	 * @param int $transformId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteTransform($transformId)
	{
		craft()->db->createCommand()->delete('assettransforms', array('id' => $transformId));
		return true;
	}

	/**
	 * Update the asset transforms for the FileModel.
	 *
	 * @param AssetFileModel $fileModel
	 * @param array|object|string $transformsToUpdate
	 * @return bool
	 */
	public function updateTransforms(AssetFileModel $fileModel, $transformsToUpdate)
	{
		if (!ImageHelper::isImageManipulatable(IOHelper::getExtension($fileModel->filename)))
		{
			return true;
		}

		$sourceType = craft()->assetSources->getSourceTypeById($fileModel->sourceId);
		$imageSource = $sourceType->getImageSourcePath($fileModel);

		$deleteSource = false;
		if (!IOHelper::fileExists($imageSource))
		{
			if (!$sourceType->isRemote())
			{
				return false;
			}

			$maxCachedImageSize = craft()->config->get("maxCachedCloudImageSize");
			$localCopy = $sourceType->getLocalCopy($fileModel);

			// Resize if constrained by maxCachedImageSizes setting
			if (is_numeric($maxCachedImageSize) && $maxCachedImageSize > 0)
			{
				craft()->images->loadImage($localCopy)->scaleToFit($maxCachedImageSize, $maxCachedImageSize)->saveAs($imageSource);
			}
			// Mark for deletion, since the maxCachedImageSizes setting is either invalid or set to 0.
			else
			{
				IOHelper::move($localCopy, $imageSource);
				$deleteSource = true;
			}
		}

		if (!is_array($transformsToUpdate))
		{
			$transformsToUpdate = array($transformsToUpdate);
		}

		foreach ($transformsToUpdate as $transform)
		{
			$transform = $this->normalizeTransform($transform);
			$transformLocation = $this->_getTransformLocation($transform);

			$timeModified = $sourceType->getTimeTransformModified($fileModel, $transformLocation);

			// Create the transform if the file doesn't exist, or if it was created before the image was last updated
			// or if the transform dimensions have changed since it was last created
			if (!$timeModified || $timeModified < $fileModel->dateModified || $timeModified < $transform->dimensionChangeTime)
			{
				$targetFile = AssetsHelper::getTempFilePath(IOHelper::getExtension($fileModel->filename));
				switch ($transform->mode)
				{
					case 'fit':
					{
						craft()->images->loadImage($imageSource)->scaleToFit($transform->width, $transform->height)->saveAs($targetFile);
						break;
					}

					case 'stretch':
					{
						craft()->images->loadImage($imageSource)->resize($transform->width, $transform->height)->saveAs($targetFile);
						break;
					}

					default:
					{

						craft()->images->loadImage($imageSource)->scaleAndCrop($transform->width, $transform->height, true, $transform->position)->saveAs($targetFile);
						break;

					}

				}

				clearstatcache(true, $targetFile);
				$sourceType->putImageTransform($fileModel, $transformLocation, $targetFile);
				IOHelper::deleteFile($targetFile);
			}
		}

		if ($deleteSource)
		{
			IOHelper::deleteFile($imageSource);
		}


		return true;
	}

	/**
	 * Get a transform index row. If it doesn't exist - create one.
	 *
	 * @param AssetFileModel $file
	 * @param $transform
	 * @return AssetTransformIndexModel
	 */
	public function getTransformIndex(AssetFileModel $file, $transform)
	{
		$transform = $this->normalizeTransform($transform);
		$transformLocation = $this->_getTransformLocation($transform);

		// Check if an entry exists already
		$entry =  craft()->db->createCommand()
			->select('ti.*')
			->from('assettransformindex ti')
			->where('ti.sourceId = :sourceId AND ti.fileId = :fileId AND ti.location = :location',
			array(':sourceId' => $file->sourceId,':fileId' => $file->id, ':location' => $transformLocation))
			->queryRow();

		if ($entry)
		{
			// If the file has been indexed after any changes impacting the transform, return the record
			$indexedAfterFileModified = $entry['dateIndexed'] >= $file->dateModified->format(DateTime::MYSQL_DATETIME, DateTime::UTC);
			$indexedAfterTransformParameterChange = (!$transform->isNamedTransform() || ($transform->isNamedTransform() && $entry['dateIndexed'] >= $transform->dimensionChangeTime->format(DateTime::MYSQL_DATETIME, DateTime::UTC)));

			if ($indexedAfterFileModified && $indexedAfterTransformParameterChange)
			{
				return new AssetTransformIndexModel($entry);
			}
			else
			{
				// Delete the out-of-date record
				craft()->db->createCommand()->delete('assettransformindex',
					'sourceId = :sourceId AND fileId = :fileId AND location = :location',
					array(':sourceId' => $file->sourceId,':fileId' => $file->id, ':location' => $transformLocation));
			}
		}

		// Create a new record
		$time = new DateTime();
		$data = array(
			'fileId' => $file->id,
			'sourceId' => $file->sourceId,
			'dateIndexed' => $time->format(DateTime::MYSQL_DATETIME, DateTime::UTC),
			'location' => $transformLocation,
			'fileExists' => 0,
			'inProgress' => 0
		);

		return $this->storeTransformIndexData(new AssetTransformIndexModel($data));
	}


	/**
	 * Index a transform.
	 *
	 * @param AssetTransformIndexModel $transformIndexData
	 * @return bool
	 */
	public function generateTransform(AssetTransformIndexModel $transformIndexData)
	{
		// For _widthxheight_mode
		if (preg_match('/_(?P<width>[0-9]+|AUTO)x(?P<height>[0-9]+|AUTO)_(?P<mode>[a-z]+)_(?P<position>[a-z\-]+)/i', $transformIndexData->location, $matches))
		{
			$data = array(
				'width'      => ($matches['width']  != 'AUTO' ? $matches['width']  : null),
				'height'     => ($matches['height'] != 'AUTO' ? $matches['height'] : null),
				'mode'       => $matches['mode'],
				'position'   => $matches['position']

			);

			$parameters = $this->normalizeTransform($data);
		}
		else
		{
			$parameters = $this->normalizeTransform(mb_substr($transformIndexData->location, 1));
		}

		$sourceType = craft()->assetSources->getSourceTypeById($transformIndexData->sourceId);

		$file = craft()->assets->getFileById($transformIndexData->fileId);

		// Look for a physical file first
		$existingFileTimeModified = $sourceType->getTimeTransformModified($file, $transformIndexData->location);

		if ($existingFileTimeModified && $existingFileTimeModified >= $file->dateModified)
		{
			if (!$parameters->isNamedTransform() || ($parameters->isNamedTransform() && $existingFileTimeModified >= $parameters->dimensionChangeTime))
			{
				// We have a satisfactory match - let's call it a day.
				return true;
			}
		}

		// For named transforms we can look for exact size matches
		if ($parameters->isNamedTransform())
		{
			$alternateLocation = '_'.($parameters->width ? $parameters->width : 'AUTO').'x'.($parameters->height ? $parameters->height : '
				').'_'.$parameters->mode;

			// Look for a physical file first
			$existingFileTimeModified = $sourceType->getTimeTransformModified($file, $alternateLocation);
			if ($existingFileTimeModified && $existingFileTimeModified >= $file->dateModified)
			{
				if (!$parameters->isNamedTransform() || ($parameters->isNamedTransform() && $existingFileTimeModified >= $parameters->dimensionChangeTime))
				{
					// We have a satisfactory match and the record has been inserted already.
					// Now copy the file to the new home
					$sourceType->copyTransform($file, $alternateLocation, $transformIndexData->location);
					return true;
				}
			}

		}

		// Just create it.
		return $this->updateTransforms($file, $parameters);
	}

	/**
	 * Get a transform's subpath
	 *
	 * @param $transform
	 * @return string
	 */
	public function getTransformSubpath($transform)
	{
		return $this->_getTransformLocation($this->normalizeTransform($transform)).'/';
	}

	/**
	 * Normalize a transform from handle or a set of properties to an AssetTransformModel.
	 *
	 * @param mixed $transform
	 * @return AssetTransformModel|null
	 * @throws Exception
	 */
	public function normalizeTransform($transform)
	{
		if (!$transform)
		{
			return null;
		}
		else if (is_string($transform))
		{
			$transformModel =  $this->getTransformByHandle($transform);
			if ($transformModel)
			{
				return $transformModel;
			}
			throw new Exception(Craft::t("The transform “{handle}” cannot be found!", array('handle' => $transform)));
		}
		else if ($transform instanceof AssetTransformModel)
		{
			return $transform;
		}
		else if (is_object($transform) || is_array($transform))
		{
			return new AssetTransformModel($transform);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Store a transform index data by it's model.
	 *
	 * @param AssetTransformIndexModel $data
	 * @return AssetTransformIndexModel
	 */
	public function storeTransformIndexData(AssetTransformIndexModel $data)
	{
		if (!empty($data->id))
		{
			$id = $data->id;
			craft()->db->createCommand()->update('assettransformindex', $data->getAttributes(null, true), 'id = :id', array(':id' => $id));
		}
		else
		{
			craft()->db->createCommand()->insert('assettransformindex', $data->getAttributes(null, true));
			$data->id = craft()->db->getLastInsertID();
		}

		return $data;
	}

	/**
	 * Get a transform index model by a row id.
	 *
	 * @param $transformId
	 * @return AssetTransformIndexModel|null
	 */
	public function getTransformIndexModelById($transformId)
	{
		// Check if an entry exists already
		$entry =  craft()->db->createCommand()
			->select('ti.*')
			->from('assettransformindex ti')
			->where('ti.id = :id', array(':id' => $transformId))
			->queryRow();

		if ($entry)
		{
			return new AssetTransformIndexModel($entry);
		}

		return null;
	}

	/**
	 * Get a transform index model by a row id.
	 *
	 * @param $fileId
	 * @param $transformHandle
	 * @return AssetTransformIndexModel|null
	 */
	public function getTransformIndexModelByFileIdAndHandle($fileId, $transformHandle)
	{
		// Check if an entry exists already
		$entry =  craft()->db->createCommand()
			->select('ti.*')
			->from('assettransformindex ti')
			->where('ti.fileId = :id AND ti.location = :location', array(':id' => $fileId, ':location' => '_' . $transformHandle))
			->queryRow();

		if ($entry)
		{
			return new AssetTransformIndexModel($entry);
		}

		return null;
	}

	/**
	 * Get URL for Transform by TransformIndexId.
	 *
	 * @param $transformId
	 * @return string
	 */
	public function getUrlforTransformByIndexId($transformId)
	{
		$transformIndexModel = $this->getTransformIndexModelById($transformId);
		$file = craft()->assets->getFileById($transformIndexModel->fileId);
		$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$baseUrl = $sourceType->getBaseUrl();
		$folderPath = $baseUrl.$file->getFolder()->path;

		return $folderPath.$transformIndexModel->location.'/'.$file->filename;
	}

	/**
	 * Get URL for a transform by File Model and transform.
	 *
	 * @param AssetFileModel $file
	 * @param $transform
	 * @return string
	 */
	public function getUrlforTransformByFile($file, $transform)
	{
		// Create URL to the image
		$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$baseUrl = $sourceType->getBaseUrl();
		$folderPath = $baseUrl.$file->getFolder()->path;
		$transformPath = $this->getTransformSubpath($transform);

		return $folderPath.$transformPath.$file->filename;
	}

	/**
	 * Cleans up transforms for a source by making sure that all indexed transforms actually exist.
	 *
	 * @param $sourceId
	 */
	public function cleanUpTransformsForSource($sourceId)
	{
		$transformList = craft()->db->createCommand()
							->where('sourceId = :sourceId AND fileExists = 1', array(':sourceId' => $sourceId))
							->select('*')
							->from('assettransformindex')
							->queryAll();

		$sourceType = craft()->assetSources->getSourceTypeById($sourceId);

		foreach ($transformList as $row)
		{
				$file = craft()->assets->getFileById($row['fileId']);
				if (!$file || !$sourceType->transformExists($file, $row['location']))
				{
					craft()->db->createCommand()->delete('assettransformindex', 'id = '.$row['id']);
				}
		}
	}

	/**
	 * Get generated transform locations for a file.
	 *
	 * @param AssetFileModel $file
	 * @return array|\CDbDataReader
	 */
	public function getGeneratedTransformLocationsForFile(AssetFileModel $file)
	{
		return craft()->db->createCommand()
			->where('sourceId = :sourceId AND fileExists = 1 AND fileId = :fileId',
				array(':sourceId' => $file->sourceId, ':fileId' => $file->id))
			->select('location')
			->from('assettransformindex')
			->queryColumn();
	}

	/**
	 * Delete transform records by a file id.
	 *
	 * @param $fileId
	 */
	public function deleteTransformRecordsByFileId($fileId)
	{
		craft()->db->createCommand()->delete('assettransformindex', 'fileId = :fileId', array(':fileId' => $fileId));
	}

	/**
	 * Get a trasnform's location folder.
	 *
	 * @param AssetTransformModel $transform
	 * @return string
	 */
	private function _getTransformLocation(AssetTransformModel $transform)
	{
		return $transform->isNamedTransform() ? '_'.$transform->handle : '_'.($transform->width ? $transform->width : 'AUTO').'x'.($transform->height ? $transform->height : 'AUTO').'_'.$transform->mode.'_'.$transform->position;
	}

	/**
	 * Gets a transform's record.
	 *
	 * @param int $id
	 * @param string $handle assumed handle for image transform for nicer error messages.
	 * @return AssetTransformRecord
	 */
	private function _getTransformRecordById($id = null, $handle = "")
	{
		if ($id)
		{
			$transformRecord = AssetTransformRecord::model()->findById($id);

			if (!$transformRecord)
			{
				$this->_noTransformExists($handle);
			}
		}
		else
		{
			$transformRecord = new AssetTransformRecord();
		}

		return $transformRecord;
	}

	/**
	 * Throws a "No transform exists" exception.
	 *
	 * @access private
	 * @param int $handle
	 * @throws Exception
	 */
	private function _noTransformExists($handle)
	{
		throw new Exception(Craft::t("Can’t find the transform with handle “{handle}”", array('handle' => $handle)));
	}

	/**
	 * @return array
	 */
	private function _loadAssetTransforms()
	{
		if (is_null($this->_assetTransforms))
		{
			$this->_assetTransforms = array();
			$models = AssetTransformModel::populateModels(AssetTransformRecord::model()->ordered()->findAll());

			foreach ($models as $model)
			{
				$this->_assetTransforms[$model->handle] = $model;
			}
		}
	}

	/**
	 * Get a thumb server path by file model and size.
	 *
	 * @param $fileModel
	 * @param $size
	 * @return bool|string
	 */
	public function getThumbServerPath($fileModel, $size)
	{
		$sourceType = craft()->assetSources->getSourceTypeById($fileModel->sourceId);

		$thumbFolder = craft()->path->getAssetsThumbsPath().$size.'/';
		IOHelper::ensureFolderExists($thumbFolder);

		$thumbPath = $thumbFolder.$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);

		if (!IOHelper::fileExists($thumbPath))
		{
			$imageSource = $sourceType->getImageSourcePath($fileModel);
			$deleteSource = false;

			if (!IOHelper::fileExists($imageSource) && !$sourceType->isRemote())
			{
				return false;
			}
			elseif ($sourceType->isRemote())
			{
				$localCopy = $sourceType->getLocalCopy($fileModel);
				$maxCachedImageSize = craft()->config->get("maxCachedCloudImageSize");


				// Resize if constrained by maxCachedImageSizes setting
				if (is_numeric($maxCachedImageSize) && $maxCachedImageSize > 0)
				{
					craft()->images->loadImage($localCopy)->scaleToFit($maxCachedImageSize, $maxCachedImageSize)->saveAs($imageSource);
				}
				// Mark for deletion, since the maxCachedImageSizes setting is either invalid or set to 0.
				else
				{
					IOHelper::move($localCopy, $imageSource);
					$deleteSource = true;
				}
			}

			craft()->images->loadImage($imageSource)
				->scaleAndCrop($size, $size)
				->saveAs($thumbPath);

			if ($deleteSource)
			{
				IOHelper::deleteFile($imageSource);
			}
		}

		return $thumbPath;
	}
}
