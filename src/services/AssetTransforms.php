<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\dates\DateTime;
use craft\app\elements\Asset;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\ImageHelper;
use craft\app\helpers\IOHelper;
use craft\app\models\AssetTransformIndex;
use craft\app\models\AssetTransform as AssetTransformModel;
use craft\app\records\AssetTransform as AssetTransformRecord;
use craft\app\errors\AssetTransformException;
use craft\app\errors\AssetSourceFileNotFoundException;
use craft\app\errors\AssetSourceException;
use craft\app\errors\AssetLogicException;
use craft\app\errors\ModelValidationException;
use Exception;
use yii\base\Application;
use yii\base\Component;

/**
 * Class AssetTransforms service.
 *
 * An instance of the AssetTransforms service is globally accessible in Craft via [[Application::assetTransforms `Craft::$app->assetTransforms`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransforms extends Component
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
	private $_sourcesToBeDeleted = [];

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
			$results = $this->_createTransformQuery()->all();

			$this->_transformsByHandle = [];

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
			$transforms = [];

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
				->where('handle = :handle', [':handle' => $handle])
				->one();

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
	 * @throws AssetTransformException
	 * @throws ModelValidationException
	 * @return bool
	 */
	public function saveTransform(AssetTransformModel $transform)
	{
		if ($transform->id)
		{
			$transformRecord = AssetTransformRecord::findOne($transform->id);

			if (!$transformRecord)
			{
				throw new AssetTransformException(Craft::t('app', 'Can’t find the transform with ID “{id}”', array('id' => $transform->id)));
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
			$exception = new ModelValidationException(Craft::t('app', 'There were errors while svaing the Asset Transform.'));
			$exception->setModel($transform);

			throw $exception;
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
		Craft::$app->getDb()->createCommand()->delete('{{%assettransforms}}', ['id' => $transformId]);
		return true;
	}

	/**
	 * Get a transform index row. If it doesn't exist - create one.
	 *
	 * @param Asset  $file
	 * @param string $transform
	 *
	 * @return AssetTransformIndex
	 */
	public function getTransformIndex(Asset $file, $transform)
	{
		$transform = $this->normalizeTransform($transform);
		$transformLocation = $this->_getTransformFolderName($transform);

		// Check if an entry exists already
		$query = (new Query())
			->select('ti.*')
			->from('{{%assettransformindex}} ti')
			->where('ti.sourceId = :sourceId AND ti.fileId = :fileId AND ti.location = :location',
				[':sourceId' => $file->sourceId,':fileId' => $file->id, ':location' => $transformLocation]);

		if (is_null($transform->format))
		{
			// A generated auto-transform will have it's format set to null, but the filename will be populated.
			$query->andWhere('format IS NULL');
		}
		else
		{
			$query->andWhere('format = :format', [':format' => $transform->format]);
		}

		$entry = $query->one();

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
				return new AssetTransformIndex($entry);
			}
			else
			{
				// Delete the out-of-date record
				Craft::$app->getDb()->createCommand()->delete('{{%assettransformindex}}',
					'id = :transformIndexId',
					[':transformIndexId' => $entry['id']]);
			}
		}

		// Create a new record
		$time = new DateTime();
		$data = [
			'fileId' => $file->id,
			'format' => $transform->format,
			'sourceId' => $file->sourceId,
			'dateIndexed' => $time->format(DateTime::MYSQL_DATETIME, DateTime::UTC),
			'location' => $transformLocation,
			'fileExists' => 0,
			'inProgress' => 0
		];

		return $this->storeTransformIndexData(new AssetTransformIndex($data));
	}

	/**
	 * Get a transform URL by the transform index model.
	 *
	 * @param AssetTransformIndex $index
	 *
	 * @throws Exception
	 * @return string
	 */
	public function ensureTransformUrlByIndexModel(AssetTransformIndex $index)
	{
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
			$this->generateTransform($index);

			// Update the index
			$index->inProgress = 0;
			$index->fileExists = 1;
			$this->storeTransformIndexData($index);
		}

		return $this->getUrlForTransformByIndexId($index->id);
	}

	/**
	 * Generate a transform by a created index.
	 *
	 * @param AssetTransformIndex $index
	 *
	 * @return null
	 */
	public function generateTransform(AssetTransformIndex $index)
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

		$file = Craft::$app->assets->getFileById($index->fileId);
		$source = Craft::$app->assetSources->populateSourceType($file->getSource());
		$index->detectedFormat = !empty($index->format) ? $index->format : $this->detectAutoTransformFormat($file);

		$transformFilename = IOHelper::getFilename($file->filename, false).'.'.$index->detectedFormat;
		$index->filename = $transformFilename;

		$matchFound = false;

		// If the detected format matches the file's format, we can use the old-style formats as well so we can dig
		// through existing files. Otherwise, delete all transforms, records of it and create new.
		if ($file->getExtension() == $index->detectedFormat)
		{
			$possibleLocations = [$this->_getUnnamedTransformFolderName($transform)];

			if ($transform->isNamedTransform())
			{
				$possibleLocations[] = $this->_getNamedTransformFolderName($transform);
			}

			// We're looking for transforms that fit the bill and are not the one we are trying to find/create
			// the image for.
			$results = (new Query())
				->select('*')
				->from('{{%assettransformindex}}')
				->where('fileId = :fileId', [':fileId' => $file->id])
				->andWhere(['in', 'location', $possibleLocations])
				->andWhere('id <> :indexId', [':indexId' => $index->id])
				->all();

			foreach ($results as $result)
			{
				// If this is a named transform and indexed before dimensions last changed, this is a stale transform
				// and needs to go.
				if ($transform->isNamedTransform() && $result['dateIndexed'] < $transform->dimensionChangeTime)
				{
					$source->deleteTransform($file, new AssetTransformIndex($result));
					$this->deleteTransform($result['id']);
				}
				// Any other should do.
				else
				{
					$matchFound = $result;
				}
			}
		}

		// If we have a match, copy the file.
		if ($matchFound)
		{
			/** @var array $matchFound */
			$source->copyTransform($file, $file->getFolder(), new AssetTransformIndex($matchFound), $index);
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
	 * @throws AssetTransformException
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

			throw new AssetTransformException(Craft::t('app', 'The transform “{handle}” cannot be found!', array('handle' => $transform)));
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
	 * @param AssetTransformIndex $index
	 *
	 * @return AssetTransformIndex
	 */
	public function storeTransformIndexData(AssetTransformIndex $index)
	{
		$values = $index->toArray();

		// These do not really belong here.
		unset($values['detectedFormat']);
		unset($values['transform']);

		if (!empty($index->id))
		{
			$id = $index->id;
			Craft::$app->getDb()->createCommand()->update('{{%assettransformindex}}', $values, 'id = :id', [':id' => $id]);
		}
		else
		{
			Craft::$app->getDb()->createCommand()->insert('{{%assettransformindex}}', $values);
			$index->id = Craft::$app->getDb()->getLastInsertID();
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
		return (new Query())
			->select('id')
			->from('{{%assettransformindex}}')
			->where(['and', 'fileExists = 0', 'inProgress = 0'])
			->column();
	}

	/**
	 * Get a transform index model by a row id.
	 *
	 * @param int $transformId
	 *
	 * @return AssetTransformIndex|null
	 */
	public function getTransformIndexModelById($transformId)
	{
		// Check if an entry exists already
		$entry = (new Query())
			->select('ti.*')
			->from('{{%assettransformindex}} ti')
			->where('ti.id = :id', [':id' => $transformId])
			->one();

		if ($entry)
		{
			return new AssetTransformIndex($entry);
		}

		return null;
	}

	/**
	 * Get a transform index model by a row id.
	 *
	 * @param int    $fileId
	 * @param string $transformHandle
	 *
	 * @return AssetTransformIndex|null
	 */
	public function getTransformIndexModelByFileIdAndHandle($fileId, $transformHandle)
	{
		// Check if an entry exists already
		$entry = (new Query())
			->select('ti.*')
			->from('{{%assettransformindex}} ti')
			->where('ti.fileId = :id AND ti.location = :location', [':id' => $fileId, ':location' => '_'.$transformHandle])
			->one();

		if ($entry)
		{
			return new AssetTransformIndex($entry);
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
	 * @param AssetTransformIndex $transformIndexModel
	 *
	 * @return string
	 */
	public function getUrlForTransformByTransformIndex(AssetTransformIndex $transformIndexModel)
	{
		$file = Craft::$app->assets->getFileById($transformIndexModel->fileId);
		$sourceType = Craft::$app->assetSources->getSourceTypeById($file->sourceId);
		$baseUrl = $sourceType->getBaseUrl();
		$appendix = AssetsHelper::getUrlAppendix($sourceType, $file);

		return $baseUrl . $file->getFolder()->path . $this->getTransformSubpath($file, $transformIndexModel) . $appendix;
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
		Craft::$app->getDb()->createCommand()->delete('{{%assettransformindex}}', 'fileId = :fileId', [':fileId' => $fileId]);
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
		Craft::$app->getDb()->createCommand()->delete('{{%assettransformindex}}', 'id = :id', [':id' => $indexId]);
	}
	/**
	 * Get a thumb server path by file model and size.
	 *
	 * @param Asset $fileModel
	 * @param       $size
	 *
	 * @return bool|string
	 */
	public function getThumbServerPath(Asset $fileModel, $size)
	{
		$thumbFolder = Craft::$app->path->getAssetsThumbsPath().'/'.$size.'/';
		IOHelper::ensureFolderExists($thumbFolder);

		$extension = $this->_getThumbExtension($fileModel);

		$thumbPath = $thumbFolder.$fileModel->id.'.'.$extension;

		if (!IOHelper::fileExists($thumbPath))
		{
			$imageSource = $this->getLocalImageSource($fileModel);

			Craft::$app->images->loadImage($imageSource)
				->scaleAndCrop($size, $size)
				->saveAs($thumbPath);

			if (Craft::$app->assetSources->populateSourceType($fileModel->getSource())->isRemote())
			{
				$this->queueSourceForDeletingIfNecessary($imageSource);
			}
		}

		return $thumbPath;
	}

	/**
	 * Get a local image source to use for transforms.
	 *
	 * @param Asset $file
	 *
	 * @throws AssetSourceException
	 * @return mixed
	 */
	public function getLocalImageSource(Asset $file)
	{
		$sourceType = Craft::$app->assetSources->getSourceTypeById($file->sourceId);

		$imageSourcePath = $file->getImageTransformSourcePath();

		if (!$sourceType->isLocal())
		{
			if (!IOHelper::fileExists($imageSourcePath) || IOHelper::getFileSize($imageSourcePath) == 0)
			{
				if ($sourceType->isLocal())
				{
					throw new AssetSourceFileNotFoundException(Craft::t('Image “{file}” cannot be found.', array('file' => $file->filename)));
				}

				// Delete it just in case it's a 0-byter
				IOHelper::deleteFile($imageSourcePath, true);

				$localCopy = IOHelper::getTempFilePath($file->getExtension());

				$sourceType->saveFile($file->getUri(), $localCopy);

				if (!IOHelper::fileExists($localCopy) || IOHelper::getFileSize($localCopy) == 0)
				{
					IOHelper::deleteFile($localCopy, true);
					throw new AssetSourceException(Craft::t('Tried to download the source file for image “{file}”, but it was 0 bytes long.', array('file' => $file->filename)));
				}

				$this->storeLocalSource($localCopy, $imageSourcePath);

				// Delete the leftover data.
				$this->queueSourceForDeletingIfNecessary($imageSourcePath);
				IOHelper::deleteFile($localCopy, true);
			}
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
		return (int) Craft::$app->config->get('maxCachedCloudImageSize');
	}

	/**
	 * Deletes an image local source if required by config.
	 *
	 * @param $imageSource
	 *
	 * @return null
	 */
	public function queueSourceForDeletingIfNecessary($imageSource)
	{
		if (! ($this->getCachedCloudImageSize() > 0))
		{
			$this->_sourcesToBeDeleted[] = $imageSource;

			if (count($this->_sourcesToBeDeleted) == 1)
			{
				Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'deleteQueuedSourceFiles']);
			}
		}
	}

	/**
	 * Store a local image copy to a destination path.
	 *
	 * @param $source
	 * @param $destination
	 *
	 * @return null
	 */
	public function storeLocalSource($source, $destination = '')
	{
		$maxCachedImageSize = $this->getCachedCloudImageSize();

		// Resize if constrained by maxCachedImageSizes setting
		if ($maxCachedImageSize > 0)
		{
			Craft::$app->images->loadImage($source)->scaleToFit($maxCachedImageSize, $maxCachedImageSize)->setQuality(100)->saveAs($destination ?: $source);
		}
		else
		{
			if ($source != $destination)
			{
				IOHelper::copyFile($source, $destination);
			}
		}
	}

	/**
	 * Detect the auto web-safe format for the Assets file. Returns null, if the file is not an image.
	 *
	 * @param Asset $file
	 *
	 * @return mixed|string
	 * @throws AssetLogicException
	 */
	public function detectAutoTransformFormat(Asset $file)
	{
		if (in_array(mb_strtolower($file->getExtension()), ImageHelper::getWebSafeFormats()))
		{
			return $file->getExtension();
		}
		else if ($file->kind == 'image')
		{

			// The only reasonable way to check for transparency is with Imagick. If Imagick is not present, then
			// we fallback to jpg
			if (Craft::$app->images->isGd() || !method_exists('Imagick', 'getImageAlphaChannel'))
			{
				return 'jpg';
			}

			$source = Craft::$app->assetSources->populateSourceType($file->getSource());
			$localCopy = $source->getLocalCopy($file);

			$image = Craft::$app->images->loadImage($localCopy);

			if ($image->isTransparent())
			{
				$format = 'png';
			}
			else
			{
				$format = 'jpg';
			}

			if (!$source->isLocal())
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

		throw new AssetLogicException(Craft::t('app', 'Tried to detect the appropriate image format for a non-image!'));
	}

	/**
	 * Return a subfolder used by the Transform Index for the File.
	 *
	 * @param Asset               $file
	 * @param AssetTransformIndex $index
	 *
	 * @return mixed|string
	 */
	public function getTransformSubfolder(Asset $file, AssetTransformIndex $index)
	{
		$path = $index->location;

		if (!empty($index->filename) && $index->filename != $file->filename)
		{
			$path .= '/'.$file->id;
		}

		return $path;
	}

	/**
	 * Return the filename used by the Transform Index for the File.
	 *
	 * @param Asset               $file
	 * @param AssetTransformIndex $index
	 *
	 * @return mixed
	 */
	public function getTransformFilename(Asset $file, AssetTransformIndex $index)
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

	/**
	 * Get a transform subpath used by the Transform Index for the File.
	 *
	 * @param Asset               $file
	 * @param AssetTransformIndex $index
	 *
	 * @return string
	 */
	public function getTransformSubpath(Asset $file, AssetTransformIndex $index)
	{
		return $this->getTransformSubfolder($file, $index).'/'.$this->getTransformFilename($file, $index);
	}

	/**
	 * Delete *ALL* transform data (including thumbs and sources) associated with file.
	 *
	 * @param Asset $file
	 *
	 * @return null
	 */
	public function deleteAllTransformData(Asset $file)
	{
		$this->deleteThumbnailsForFile($file);
		$this->deleteCreatedTransformsForFile($file);
		$this->deleteTransformIndexDataByFileId($file->id);

		IOHelper::deleteFile(Craft::$app->path->getAssetsImageSourcePath().$file->id.'.'.IOHelper::getExtension($file->filename), true);
	}

	/**
	 * Delete all the generated thumbnails for the file.
	 *
	 * @param Asset $file
	 *
	 * @return null
	 */
	public function deleteThumbnailsForFile(Asset $file)
	{
		$thumbFolders = IOHelper::getFolderContents(Craft::$app->path->getAssetsThumbsPath());

		foreach ($thumbFolders as $folder)
		{
			if (is_dir($folder))
			{
				IOHelper::deleteFile($folder.'/'.$file->id.'.'.$this->_getThumbExtension($file));
			}
		}
	}

	/**
	 * Delete created transforms for a file.
	 *
	 * @param Asset $file
	 */
	public function deleteCreatedTransformsForFile(Asset $file)
	{
		$indexModels = $this->getAllCreatedTransformsForFile($file);

		$source = Craft::$app->assetSources->populateSourceType($file->getSource());

		foreach ($indexModels as $index)
		{
			$source->deleteTransform($file, $index);
		}
	}

	/**
	 * Get an array of AssetTransformIndex models for all created transforms for a file.
	 *
	 * @param Asset $file
	 *
	 * @return array
	 */
	public function getAllCreatedTransformsForFile(Asset $file)
	{
		$transforms = (new Query())
			->select('*')
			->from('{{%assettransformindex}}')
			->where('fileId = :fileId', [':fileId' => $file->id])
			->all();

		foreach ($transforms as $key => $value)
		{
			$transforms[$key] = AssetTransformIndex::create($value);
		}

		return $transforms;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a Query object prepped for retrieving transforms.
	 *
	 * @return Query
	 */
	private function _createTransformQuery()
	{
		return (new Query())
			->select(['id', 'name', 'handle', 'mode', 'position', 'height', 'width', 'format', 'quality', 'dimensionChangeTime'])
			->from('{{%assettransforms}}')
			->orderBy('name');
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
	 * @param Asset               $file
	 * @param AssetTransformIndex $index
	 *
	 * @throws AssetTransformException if the AssetTransformIndex cannot be determined to have a transform
	 * @return null
	 */
	private function _createTransformForFile(Asset $file, AssetTransformIndex $index)
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
				throw new AssetTransformException(Craft::t('app', 'Unable to recognize the transform for this transform index!'));
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

		$sourceType = Craft::$app->assetSources->populateSourceType($file->getSource());
		$imageSource = $file->getTransformSource();
		$quality = $transform->quality ? $transform->quality : Craft::$app->config->get('defaultImageQuality');

		$image = Craft::$app->images->loadImage($imageSource);
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

		$createdTransform = IOHelper::getTempFilePath($index->detectedFormat);
		$image->saveAs($createdTransform);

		clearstatcache(true, $createdTransform);
		$sourceType->putImageTransform($file, $index, $createdTransform);
		IOHelper::deleteFile($createdTransform);

		if (!Craft::$app->assetSources->populateSourceType($file->getSource())->isLocal())
		{
			$this->queueSourceForDeletingIfNecessary($imageSource);
		}

		return;
	}

	/**
	 * Return the thumbnail extension for a file.
	 *
	 * @param Asset $file
	 *
	 * @return string
	 */
	private function _getThumbExtension(Asset $file)
	{
		// For non-web-safe formats we go with jpg.
		if (!in_array(mb_strtolower(IOHelper::getExtension($file->filename)), ImageHelper::getWebSafeFormats()))
		{
			return 'jpg';
		}
		else
		{
			return $file->getExtension();
		}
	}
}
