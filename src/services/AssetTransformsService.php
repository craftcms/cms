<?php
namespace Craft;

/**
 * Class AssetTransformsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class AssetTransformsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_transformsByHandle;

	/**
	 * @var bool
	 */
	private $_fetchedAllTransforms = false;

	/**
	 * @var array
	 */
	private $_sourcesToBeDeleted = array();

	// Public Methods
	// =========================================================================

	/**
	 * Returns all named asset transforms.
	 *
	 * @param string|null $indexBy
	 *
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
	 * @param string $handle
	 *
	 * @return AssetTransformModel|null
	 */
	public function getTransformByHandle($handle)
	{
		// If we've already fetched all transforms we can save ourselves a trip to the DB for transform handles that
		// don't exist
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

		$transformRecord->mode     = $transform->mode;
		$transformRecord->position = $transform->position;
		$transformRecord->width    = $transform->width;
		$transformRecord->height   = $transform->height;
		$transformRecord->quality  = $transform->quality;
		$transformRecord->format   = $transform->format;

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
	 * Get a transform index row. If it doesn't exist - create one.
	 *
	 * @param AssetFileModel $file
	 * @param string         $transform
	 * @return AssetTransformIndexModel
	 */
	public function getTransformIndex(AssetFileModel $file, $transform)
	{
		$transform = $this->normalizeTransform($transform);
		$transformLocation = $this->_getTransformFolderName($transform);

		// Check if an entry exists already
		$query = craft()->db->createCommand()
			->select('ti.*')
			->from('assettransformindex ti')
			->where('ti.sourceId = :sourceId AND ti.fileId = :fileId AND ti.location = :location',
				array(':sourceId' => $file->sourceId,':fileId' => $file->id, ':location' => $transformLocation));

		if (is_null($transform->format))
		{
			// A generated auto-transform will have it's format set to null, but the filename will be populated.
			$query->andWhere('ti.filename IS NOT NULL AND format IS NULL');
		}
		else
		{
			$query->andWhere('ti.filename IS NOT NULL AND format = :format', array(':format' => $transform->format));
		}

		$entry = $query->queryRow();

		if ($entry)
		{
			// If the file has been indexed after any changes impacting the transform, return the record
			$indexedAfterFileModified = $entry['dateIndexed'] >= $file->dateModified->format(DateTime::MYSQL_DATETIME, DateTime::UTC);
			$indexedAfterTransformParameterChange =
				(!$transform->isNamedTransform()
					|| ($transform->isNamedTransform()
						&& $entry['dateIndexed'] >= $transform->dimensionChangeTime->format(DateTime::MYSQL_DATETIME, DateTime::UTC)));

			if ($indexedAfterFileModified && $indexedAfterTransformParameterChange)
			{
				return new AssetTransformIndexModel($entry);
			}
			else
			{
				// Delete the out-of-date record
				craft()->db->createCommand()->delete('assettransformindex',
					'id = :transformIndexId',
					array(':transformIndexId' => $entry['id']));
			}
		}

		// Create a new record
		$time = new DateTime();
		$data = array(
			'fileId' => $file->id,
			'format' => $transform->format,
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

		return $this->getUrlForTransformByIndexId($index->id);
	}

	/**
	 * Generate a transform by a created index.
	 *
	 * @param AssetTransformIndexModel $index
	 * @return null
	 */
	public function generateTransform(AssetTransformIndexModel $index)
	{
		// For _widthxheight_mode
		if (preg_match('/_(?P<width>[0-9]+|AUTO)x(?P<height>[0-9]+|AUTO)_(?P<mode>[a-z]+)_(?P<position>[a-z\-]+)(_(?P<quality>[0-9]+))?/i', $index->location, $matches))
		{
			$transform           = new AssetTransformModel();
			$transform->width    = ($matches['width']  != 'AUTO' ? $matches['width']  : null);
			$transform->height   = ($matches['height'] != 'AUTO' ? $matches['height'] : null);
			$transform->mode     = $matches['mode'];
			$transform->position = $matches['position'];
			$transform->quality  = isset($matches['quality']) ? $matches['quality'] : null;
		}
		else
		{
			// Load the dimensions for named transforms and merge with file-specific information.
			$transform = $this->normalizeTransform(mb_substr($index->location, 1));
		}

		$index->transform = $transform;

		$file = craft()->assets->getFileById($index->fileId);
		$source = craft()->assetSources->populateSourceType($file->getSource());
		$index->detectedFormat = !empty($index->format) ? $index->format : $this->detectAutoTransformFormat($file);

		$transformFilename = IOHelper::getFileName($file->filename, false).'.'.$index->detectedFormat;
		$index->filename = $transformFilename;

		$usableTransforms = array();

		// If the detected format matches the file's format, we can use the
		// old-style formats as well so we can dig through existing files
		// Otherwise, delete all transforms, records of it and create new.
		if ($file->getExtension() == $index->detectedFormat)
		{
			$possibleLocations = array($this->_getUnnamedTransformFolderName($transform));

			if ($transform->isNamedTransform())
			{
				$possibleLocations[] = $this->_getNamedTransformFolderName($transform);
			}

			// We're looking for transforms that fit the bill and are not the
			// one we are trying to find/create the image for.
			$results = craft()->db->createCommand()
				->select('*')
				->from('assettransformindex')
				->where('fileId = :fileId', array(':fileId' => $file->id))
				->andWhere(array('in', 'location', $possibleLocations))
				->andWhere('id <> :indexId', array(':indexId' => $index->id))
				->queryAll();

			foreach ($results as $result)
			{
				// If this is a named transform and indexed before dimensions
				// last changed, this is a stale transform and needs to go.
				if ($transform->isNamedTransform() && $result['dateIndexed'] < $transform->dimensionChangeTime)
				{
					$source->deleteTransform($file, new AssetTransformIndexModel($result));
					$this->deleteTransform($result['id']);
				}
				// Any other should do.
				else
				{
					$usableTransforms[] = $result;
				}
			}
		}

		if (!empty($usableTransforms))
		{
			foreach ($usableTransforms as $key => $usableTransform)
			{
				// Copy the first transform in it's new home
				if ($key == 0)
				{
					$source->copyTransform($file, new AssetTransformIndexModel($usableTransform), $index);
				}

				// For all transforms that matched - if this is the old style
				// then duplicate this both for AUTO and the appropriate format
				if (empty($usableTransform['filename']))
				{
					// Delete the old record
					$this->deleteTransformIndex($usableTransform['id']);

					unset ($usableTransform['id']);
					$newIndex = new AssetTransformIndexModel($usableTransform);
					$newIndex->filename = $transformFilename;

					// And add the two new ones.

					// This is correct, because, for example, JPG file format
					// detection will resolve to "jpg" anyway, so we can store
					// the AUTO version as well.
					$newIndex->format = null;

					// Since we're making new transforms, chances are we're
					// stepping on the toes of the transform index that started
					// this. Make sure we don't make one copy too many.
					if ($newIndex->location != $index->location || $newIndex->format != $index->format)
					{
						$this->storeTransformIndexData($newIndex);
					}

					// And one for detected as well.
					$newIndex->id = null;
					$newIndex->format = $index->detectedFormat;

					if ($newIndex->location != $index->location || $newIndex->format != $index->format)
					{
						$this->storeTransformIndexData($newIndex);
					}

					$result['format'] = $index->format;
				}
			}

		}
		else
		{
			$this->_createTransformForFile($file, $index);
		}
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
	 *
	 * @return AssetTransformIndexModel
	 */
	public function storeTransformIndexData(AssetTransformIndexModel $index)
	{
		$values = $index->getAttributes(null, true);

		// These do not really belong here.
		unset($values['detectedFormat']);
		unset($values['transform']);

		if (!empty($index->id))
		{
			$id = $index->id;
			craft()->db->createCommand()->update('assettransformindex', $values, 'id = :id', array(':id' => $id));
		}
		else
		{
			craft()->db->createCommand()->insert('assettransformindex', $values);
			$index->id = craft()->db->getLastInsertID();
		}

		return $index;
	}

	/**
	 * Returns a list of pending transform index IDs.
	 *
	 * @return array
	 */
	public function getPendingTransformIndexIds()
	{
		return craft()->db->createCommand()
			->select('id')
			->from('assettransformindex')
			->where(array('and', 'fileExists = 0', 'inProgress = 0'))
			->queryColumn();
	}

	/**
	 * Get a transform index model by a row id.
	 *
	 * @param int $transformId
	 *
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
	 * @param int    $fileId
	 * @param string $transformHandle
	 *
	 * @return AssetTransformIndexModel|null
	 */
	public function getTransformIndexModelByFileIdAndHandle($fileId, $transformHandle)
	{
		// Check if an entry exists already
		$entry =  craft()->db->createCommand()
			->select('ti.*')
			->from('assettransformindex ti')
			->where('ti.fileId = :id AND ti.location = :location', array(':id' => $fileId, ':location' => '_'.$transformHandle))
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
	 *
	 * @return string
	 */
	public function getUrlForTransformByIndexId($transformId)
	{
		$transformIndexModel = $this->getTransformIndexModelById($transformId);
		return $this->getUrlForTransformByTransformIndex($transformIndexModel);

	}

	/**
	 * Get URL for Transform by the transform index model.
	 *
	 * @param AssetTransformIndexModel $transformIndexModel
	 *
	 * @return string
	 */
	public function getUrlForTransformByTransformIndex(AssetTransformIndexModel $transformIndexModel)
	{
		$file = craft()->assets->getFileById($transformIndexModel->fileId);
		$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$baseUrl = $sourceType->getBaseUrl();
		$folderPath = $file->getFolder()->path;
		$appendix = AssetsHelper::getUrlAppendix($sourceType, $file);

		return $baseUrl.$folderPath.$transformIndexModel->location.'/'.$file->id.'/'.$transformIndexModel->filename . $appendix;
	}

	/**
	 * Cleans up transforms for a source by making sure that all indexed transforms actually exist.
	 *
	 * @param int $sourceId
	 *
	 * @return null
	 */
	public function cleanUpTransformsForSource($sourceId)
	{
		$transformList = craft()->db->createCommand()
		        ->where('sourceId = :sourceId AND fileExists = 1', array(':sourceId' => $sourceId))
		        ->select('*')
		        ->from('assettransformindex')
		        ->queryAll();

		$sourceType = craft()->assetSources->getSourceTypeById($sourceId);

		// TODO: implement this.

		foreach ($transformList as $row)
		{
			$file = craft()->assets->getFileById($row['fileId']);

			/*if (!$file || !$sourceType->transformExists($file, $row['location']))
			{
				craft()->db->createCommand()->delete('assettransformindex', 'id = '.$row['id']);
			}*/
		}
	}

	/**
	 * Get generated transform locations for a file.
	 *
	 * @param AssetFileModel $file
	 *
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
	 * @param int $fileId
	 *
	 * @return null
	 */
	public function deleteTransformIndexDataByFileId($fileId)
	{
		craft()->db->createCommand()->delete('assettransformindex', 'fileId = :fileId', array(':fileId' => $fileId));
	}

	/**
	 * Delete a transform index by.
	 *
	 * @param int $indexId
	 *
	 * @return null
	 */
	public function deleteTransformIndex($indexId)
	{
		craft()->db->createCommand()->delete('assettransformindex', 'id = :id', array(':id' => $indexId));
	}
	/**
	 * Get a thumb server path by file model and size.
	 *
	 * @param $fileModel
	 * @param $size
	 *
	 * @return bool|string
	 */
	public function getThumbServerPath(AssetFileModel $fileModel, $size)
	{
		$thumbFolder = craft()->path->getAssetsThumbsPath().$size.'/';
		IOHelper::ensureFolderExists($thumbFolder);

		// For non-web-safe formats we go with jpg.
		$extension = StringHelper::toLowerCase(pathinfo($fileModel->filename, PATHINFO_EXTENSION));
		if (!in_array($extension, ImageHelper::getWebSafeFormats()))
		{
			$extension = 'jpg';
		}
		$thumbPath = $thumbFolder.$fileModel->id.'.'.$extension;

		if (!IOHelper::fileExists($thumbPath))
		{
			$imageSource = $this->getLocalImageSource($fileModel);

			craft()->images->loadImage($imageSource)
				->scaleAndCrop($size, $size)
				->saveAs($thumbPath);

			if (craft()->assetSources->populateSourceType($fileModel->getSource())->isRemote())
			{
				$this->queueSourceForDeletingIfNecessary($imageSource);
			}
		}

		return $thumbPath;
	}

	/**
	 * Get a local image source to use for transforms.
	 *
	 * @param $file
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function getLocalImageSource(AssetFileModel $file)
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
			$this->queueSourceForDeletingIfNecessary($imageSourcePath);
		}

		$file->setTransformSource($imageSourcePath);

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
	public function queueSourceForDeletingIfNecessary($imageSource)
	{
		if (! ($this->getCachedCloudImageSize() > 0))
		{
			$this->_sourcesToBeDeleted[] = $imageSource;

			if (count($this->_sourcesToBeDeleted) == 1)
			{
				craft()->onEndRequest = array($this, 'deleteQueuedSourceFiles');
			}
		}
	}

	/**
	 * Store a local image copy to a destination path.
	 *
	 * @param $localCopy
	 * @param $destination
	 *
	 * @return null
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

	/**
	 * Detect the auto web-safe format for the Assets file. Returns null, if the file is not an image.
	 *
	 * @param AssetFileModel $file
	 * @return mixed|string
	 * @throws Exception
	 */
	public function detectAutoTransformFormat(AssetFileModel $file)
	{
		if (in_array($file->getExtension(), ImageHelper::getWebSafeFormats()))
		{
			return $file->getExtension();
		}
		else if ($file->kind == "image")
		{

			// The only reasonable way to check for transparency is with Imagick
			// If Imagick is not present, then we fallback to jpg
			if (craft()->images->isGd() || !method_exists("Imagick", "getImageAlphaChannel"))
			{
				return 'jpg';
			}

			$source = craft()->assetSources->populateSourceType($file->getSource());
			$localCopy = $source->getLocalCopy($file);

			$image = craft()->images->loadImage($localCopy);

			if ($image->isTransparent())
			{
				$format = 'png';
			}
			else
			{
				$format = 'jpg';
			}

			if ($source->isRemote())
			{
				// Store for potential later use and queue for deletion if needed.
				$file->setTransformSource($localCopy);
				$this->queueSourceForDeletingIfNecessary($localCopy);
			}
			else
			{
				// For local, though, we just delete the temp file.
				IOHelper::deleteFile($localCopy);
			}

			return $format;
		}

		throw new Exception(Craft::t("Tried to detect the appropriate image format for a non-image!"));
	}

	/**
	 * Return a subfolder used by the Transform Index for the File.
	 *
	 * @param AssetFileModel $file
	 * @param AssetTransformIndexModel $index
	 * @return mixed|string
	 */
	public function getTransformSubfolder(AssetFileModel $file, AssetTransformIndexModel $index)
	{
		$path = $index->location;

		if (!empty($index->filename))
		{
			$path .= '/'.$file->id;
		}

		return $path;
	}

	/**
	 * Return the filename used by the Transform Index for the File.
	 *
	 * @param $file
	 * @param $index
	 * @return mixed
	 */
	public function getTransformFilename($file, $index)
	{
		if (empty($index->filename))
		{
			return $file->filename;
		}
		else
		{
			return $index->filename;
		}
	}

	public function getTransformSubpath($file, $index)
	{
		return $this->getTransformSubfolder($file, $index).'/'.$this->getTransformFilename($file, $index);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a DbCommand object prepped for retrieving transforms.
	 *
	 * @return DbCommand
	 */
	private function _createTransformQuery()
	{
		return craft()->db->createCommand()
			->select('id, name, handle, mode, position, height, width, format, quality, dimensionChangeTime')
			->from('assettransforms')
			->order('name');
	}

	/**
	 * Returns a transform's folder name.
	 *
	 * @param AssetTransformModel $transform
	 *
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
	 *
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
	 *
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
	 * Create a transform for the file by the transform index.
	 *
	 * @param AssetFileModel           $file
	 * @param AssetTransformIndexModel $index
	 *
	 * @throws Exception if the AssetTransformIndexModel cannot be determined to
	 *                   have transform
	 * @return null
	 */
	private function _createTransformForFile(AssetFileModel $file, AssetTransformIndexModel $index)
	{
		if (!ImageHelper::isImageManipulatable(IOHelper::getExtension($file->filename)))
		{
			return;
		}

		if (empty($index->transform))
		{
			$transform = $this->normalizeTransform(mb_substr($index->location, 1));

			if (empty($transform))
			{
				throw new Exception(Craft::t("Unable to recognize the transform for this transform index!"));
			}
		}
		else
		{
			$transform = $index->transform;
		}

		if (!isset($index->detectedFormat))
		{
			$index->detectedFormat = !empty($index->format) ? $index->format : $this->detectAutoTransformFormat($file);
		}

		$sourceType = craft()->assetSources->populateSourceType($file->getSource());
		$imageSource = $file->getTransformSource();
		$quality = $transform->quality ? $transform->quality : craft()->config->get('defaultImageQuality');

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

		$createdTransform = AssetsHelper::getTempFilePath($index->detectedFormat);
		$image->saveAs($createdTransform);

		clearstatcache(true, $createdTransform);
		$sourceType->putImageTransform($file, $index, $createdTransform);
		IOHelper::deleteFile($createdTransform);

		if (craft()->assetSources->populateSourceType($file->getSource())->isRemote())
		{
			$this->queueSourceForDeletingIfNecessary($imageSource);
		}

		return;
	}
}
