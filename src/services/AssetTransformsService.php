<?php
namespace Craft;

/**
 * Class AssetTransformsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class AssetTransformsService extends BaseApplicationComponent
{
	private $_transformsByHandle;
	private $_fetchedAllTransforms = false;

	/**
	 * Returns all named asset transforms.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllTransforms($indexBy = null)
	{
		if (!$this->_fetchedAllTransforms)
		{
			$results = $this->_createTransformQuery()->queryAll();

			$this->_transformsByHandle = array();

			foreach ($results as $result)
			{
				$transform = new AssetTransformModel($result);
				$this->_transformsByHandle[$transform->handle] = $transform;
			}

			$this->_fetchedAllTransforms = true;
		}

		if ($indexBy == 'handle')
		{
			$transforms = $this->_transformsByHandle;
		}
		else if (!$indexBy)
		{
			$transforms = array_values($this->_transformsByHandle);
		}
		else
		{
			$transforms = array();

			foreach ($this->_transformsByHandle as $transform)
			{
				$transforms[$transform->$indexBy] = $transform;
			}
		}

		return $transforms;
	}

	/**
	 * Returns an asset transform by its handle.
	 *
	 * @param $handle
	 * @return AssetTransformModel|null
	 */
	public function getTransformByHandle($handle)
	{
		// If we've already fetched all transforms we can save ourselves a trip to the DB
		// for transform handles that don't exist
		if (!$this->_fetchedAllTransforms &&
			(!isset($this->_transformsByHandle) || !array_key_exists($handle, $this->_transformsByHandle))
		)
		{
			$result = $this->_createTransformQuery()
				->where('handle = :handle', array(':handle' => $handle))
				->queryRow();

			if ($result)
			{
				$transform = new AssetTransformModel($result);
			}
			else
			{
				$transform = null;
			}

			$this->_transformsByHandle[$handle] = $transform;
		}

		if (isset($this->_transformsByHandle[$handle]))
		{
			return $this->_transformsByHandle[$handle];
		}
	}


	/**
	 * Saves an asset transform.
	 *
	 * @param AssetTransformModel $transform
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function saveTransform(AssetTransformModel $transform)
	{
		if ($transform->id)
		{
			$transformRecord = AssetTransformRecord::model()->findById($transform->id);

			if (!$transformRecord)
			{
				throw new Exception(Craft::t('Can’t find the transform with ID “{id}”', array('id' => $transform->id)));
			}
		}
		else
		{
			$transformRecord = new AssetTransformRecord();
		}

		$transformRecord->name = $transform->name;
		$transformRecord->handle = $transform->handle;

		$heightChanged = $transformRecord->width != $transform->width || $transformRecord->height != $transform->height;
		$modeChanged = $transformRecord->mode != $transform->mode || $transformRecord->position != $transform->position;
		$qualityChanged = $transformRecord->quality != $transform->quality;

		if ($heightChanged || $modeChanged || $qualityChanged)
		{
			$transformRecord->dimensionChangeTime = new DateTime('@'.time());
		}

		$transformRecord->mode = $transform->mode;
		$transformRecord->position = $transform->position;
		$transformRecord->width = $transform->width;
		$transformRecord->height = $transform->height;
		$transformRecord->quality = $transform->quality;

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
	 *
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
	 * @param AssetFileModel $file
	 * @param array|object|string $transformsToUpdate
	 * @return bool
	 */
	public function updateTransforms(AssetFileModel $file, $transformsToUpdate)
	{
		if (!ImageHelper::isImageManipulatable(IOHelper::getExtension($file->filename)))
		{
			return true;
		}

		$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$imageSource = $this->getLocalImageSource($file);

		if (!is_array($transformsToUpdate))
		{
			$transformsToUpdate = array($transformsToUpdate);
		}

		foreach ($transformsToUpdate as $transform)
		{
			$transform = $this->normalizeTransform($transform);
			$quality = $transform->quality ? $transform->quality : craft()->config->get('defaultImageQuality');
			$transformLocation = $this->_getTransformFolderName($transform);

			$timeModified = $sourceType->getTimeTransformModified($file, $transformLocation);

			// Create the transform if the file doesn't exist, or if it was created before the image was last updated
			// or if the transform dimensions have changed since it was last created
			if (!$timeModified || $timeModified < $file->dateModified || $timeModified < $transform->dimensionChangeTime)
			{
				$image = craft()->images->loadImage($imageSource);
				$image->setQuality($quality);

				switch ($transform->mode)
				{
					case 'fit':
					{
						$image->scaleToFit($transform->width, $transform->height);
						break;
					}

					case 'stretch':
					{
						$image->resize($transform->width, $transform->height);
						break;
					}

					default:
					{
						$image->scaleAndCrop($transform->width, $transform->height, true, $transform->position);
						break;
					}
				}

				$targetFile = AssetsHelper::getTempFilePath(IOHelper::getExtension($file->filename));
				$image->saveAs($targetFile);

				clearstatcache(true, $targetFile);
				$sourceType->putImageTransform($file, $transformLocation, $targetFile);
				IOHelper::deleteFile($targetFile);
			}
		}

		if (craft()->assetSources->populateSourceType($file->getSource())->isRemote())
		{
			$this->deleteSourceIfNecessary($imageSource);
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
		$transformLocation = $this->_getTransformFolderName($transform);

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
	 * Get a transform URL by the transform index model.
	 *
	 * @param AssetTransformIndexModel $index
	 *
	 * @throws Exception
	 * @return string
	 */
	public function ensureTransformUrlByIndexModel(AssetTransformIndexModel $index)
	{
		if (!$index)
		{
			throw new Exception(Craft::t('No asset image transform exists with that ID.'));
		}

		// Make sure we're not in the middle of working on this transform from a separate request
		if ($index->inProgress)
		{
			for ($safety = 0; $safety < 100; $safety++)
			{
				// Wait a second!
				sleep(1);
				ini_set('max_execution_time', 120);

				$index = $this->getTransformIndexModelById($index->id);

				// Is it being worked on right now?
				if ($index->inProgress)
				{
					// Make sure it hasn't been working for more than 30 seconds. Otherwise give up on the other request.
					$time = new DateTime();

					if ($time->getTimestamp() - $index->dateUpdated->getTimestamp() < 30)
					{
						continue;
					}
					else
					{
						$index->dateUpdated = new DateTime();
						$this->storeTransformIndexData($index);
						break;
					}
				}
				else
				{
					// Must be done now!
					break;
				}
			}
		}

		if (!$index->fileExists)
		{
			// Mark the transform as in progress
			$index->inProgress = 1;
			$this->storeTransformIndexData($index);

			// Generate the transform
			$result = $this->generateTransform($index);

			// Update the index
			$index->inProgress = 0;

			if ($result)
			{
				$index->fileExists = 1;
			}

			$this->storeTransformIndexData($index);

			if (!$result)
			{
				throw new Exception(Craft::t("The requested image could not be found!"));
			}
		}

		return $this->getUrlforTransformByIndexId($index->id);
	}

	/**
	 * Generates a transform.
	 *
	 * @param AssetTransformIndexModel $index
	 * @return bool
	 */
	public function generateTransform(AssetTransformIndexModel $index)
	{
		// For _widthxheight_mode
		if (preg_match('/_(?P<width>[0-9]+|AUTO)x(?P<height>[0-9]+|AUTO)_(?P<mode>[a-z]+)_(?P<position>[a-z\-]+)(_(?P<quality>[0-9]+))?/i', $index->location, $matches))
		{
			$data = array(
				'width'      => ($matches['width']  != 'AUTO' ? $matches['width']  : null),
				'height'     => ($matches['height'] != 'AUTO' ? $matches['height'] : null),
				'mode'       => $matches['mode'],
				'position'   => $matches['position'],
			);
			$data['quality'] = isset($matches['quality']) ? $matches['quality'] : null;

			$transform = $this->normalizeTransform($data);
		}
		else
		{
			$transform = $this->normalizeTransform(mb_substr($index->location, 1));
		}

		$sourceType = craft()->assetSources->getSourceTypeById($index->sourceId);

		$file = craft()->assets->getFileById($index->fileId);

		// Look for a physical file first
		$existingFileTimeModified = $sourceType->getTimeTransformModified($file, $index->location);

		if ($existingFileTimeModified && $existingFileTimeModified >= $file->dateModified)
		{
			if (!$transform->isNamedTransform() || ($transform->isNamedTransform() && $existingFileTimeModified >= $transform->dimensionChangeTime))
			{
				// We have a satisfactory match - let's call it a day.
				return true;
			}
		}

		// For named transforms we can look for exact size matches
		if ($transform->isNamedTransform())
		{
			// Look for a physical file first
			$alternateLocation = $this->_getUnnamedTransformFolderName($transform);
			$existingFileTimeModified = $sourceType->getTimeTransformModified($file, $alternateLocation);

			if ($existingFileTimeModified && $existingFileTimeModified >= $file->dateModified)
			{
				if (!$transform->isNamedTransform() || ($transform->isNamedTransform() && $existingFileTimeModified >= $transform->dimensionChangeTime))
				{
					// We have a satisfactory match and the record has been inserted already.
					// Now copy the file to the new home
					$sourceType->copyTransform($file, $alternateLocation, $index->location);
					return true;
				}
			}

		}

		// Just create it.
		return $this->updateTransforms($file, $transform);
	}

	/**
	 * Get a transform's subpath
	 *
	 * @param $transform
	 * @return string
	 */
	public function getTransformSubpath($transform)
	{
		return $this->_getTransformFolderName($this->normalizeTransform($transform)).'/';
	}

	/**
	 * Normalize a transform from handle or a set of properties to an AssetTransformModel.
	 *
	 * @param mixed $transform
	 *
	 * @throws Exception
	 * @return AssetTransformModel|null
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
	 * @param AssetTransformIndexModel $index
	 * @return AssetTransformIndexModel
	 */
	public function storeTransformIndexData(AssetTransformIndexModel $index)
	{
		if (!empty($index->id))
		{
			$id = $index->id;
			craft()->db->createCommand()->update('assettransformindex', $index->getAttributes(null, true), 'id = :id', array(':id' => $id));
		}
		else
		{
			craft()->db->createCommand()->insert('assettransformindex', $index->getAttributes(null, true));
			$index->id = craft()->db->getLastInsertID();
		}

		return $index;
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
		$index = $this->getTransformIndexModelById($transformId);
		$file = craft()->assets->getFileById($index->fileId);
		$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$baseUrl = $sourceType->getBaseUrl();
		$folderPath = $baseUrl.$file->getFolder()->path;

		return $folderPath.$index->location.'/'.$file->filename;
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
		$transformPath = $this->getTransformSubpath($transform);
		return AssetsHelper::generateUrl($sourceType, $file, $transformPath);
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
	 * Get a thumb server path by file model and size.
	 *
	 * @param $file
	 * @param $size
	 * @return bool|string
	 */
	public function getThumbServerPath(AssetFileModel $file, $size)
	{
		$thumbFolder = craft()->path->getAssetsThumbsPath().$size.'/';
		IOHelper::ensureFolderExists($thumbFolder);

		$thumbPath = $thumbFolder.$file->id.'.'.pathinfo($file->filename, PATHINFO_EXTENSION);

		if (!IOHelper::fileExists($thumbPath))
		{
			$imageSource = $this->getLocalImageSource($file);

			craft()->images->loadImage($imageSource)
				->scaleAndCrop($size, $size)
				->saveAs($thumbPath);

			if (craft()->assetSources->populateSourceType($file->getSource())->isRemote())
			{
				$this->deleteSourceIfNecessary($imageSource);
			}
		}

		return $thumbPath;
	}

	// Private methods

	/**
	 * Returns a DbCommand object prepped for retrieving transforms.
	 *
	 * @return DbCommand
	 */
	private function _createTransformQuery()
	{
		return craft()->db->createCommand()
			->select('id, name, handle, mode, position, height, width, quality, dimensionChangeTime')
			->from('assettransforms')
			->order('name');
	}

	/**
	 * Returns a trasnform's folder name.
	 *
	 * @param AssetTransformModel $transform
	 * @return string
	 */
	private function _getTransformFolderName(AssetTransformModel $transform)
	{
		if ($transform->isNamedTransform())
		{
			return $this->_getNamedTransformFolderName($transform);
		}
		else
		{
			return $this->_getUnnamedTransformFolderName($transform);
		}
	}

	/**
	 * Returns a named transform's folder name.
	 *
	 * @param AssetTransformModel $transform
	 * @return string
	 */
	private function _getNamedTransformFolderName(AssetTransformModel $transform)
	{
		return '_'.$transform->handle;
	}

	/**
	 * Returns an unnamed transform's folder name.
	 *
	 * @param AssetTransformModel $transform
	 * @return string
	 */
	private function _getUnnamedTransformFolderName(AssetTransformModel $transform)
	{
		return '_'.($transform->width ? $transform->width : 'AUTO').'x'.($transform->height ? $transform->height : 'AUTO') .
		       '_'.($transform->mode) .
		       '_'.($transform->position) .
		       ($transform->quality ? '_'.$transform->quality : '');
	}

	/**
	 * Get a local image source to use for transforms.
	 *
	 * @param $file
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function getLocalImageSource($file)
	{
		$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$imageSourcePath = $sourceType->getImageSourcePath($file);

		if (!IOHelper::fileExists($imageSourcePath))
		{
			if (!$sourceType->isRemote())
			{
				throw new Exception(Craft::t("Image “{file}” cannot be found.", array('file' => $file->filename)));
			}

			$localCopy = $sourceType->getLocalCopy($file);
			$this->storeLocalSource($localCopy, $imageSourcePath);
		}

		return $imageSourcePath;
	}

	/**
	 * Get the size of max cached cloud images dimension.
	 *
	 * @return int
	 */
	public function getCachedCloudImageSize()
	{
		return (int) craft()->config->get('maxCachedCloudImageSize');
	}

	/**
	 * Deletes an image local source if required by config.
	 *
	 * @param $imageSource
	 */
	public function deleteSourceIfNecessary($imageSource)
	{
		if (! ($this->getCachedCloudImageSize() > 0))
		{
			IOHelper::deleteFile($imageSource);
		}
	}

	/**
	 * Store a local image copy to a destination path.
	 *
	 * @param $localCopy
	 * @param $destination
	 */
	public function storeLocalSource($localCopy, $destination)
	{
		$maxCachedImageSize = $this->getCachedCloudImageSize();

		// Resize if constrained by maxCachedImageSizes setting
		if ($maxCachedImageSize > 0)
		{
			craft()->images->loadImage($localCopy)->scaleToFit($maxCachedImageSize, $maxCachedImageSize)->setQuality(100)->saveAs($destination);

			if ($localCopy != $destination)
			{
				IOHelper::deleteFile($localCopy);
			}
		}
		else
		{
			if ($localCopy != $destination)
			{
				IOHelper::move($localCopy, $destination);
			}
		}
	}
}
