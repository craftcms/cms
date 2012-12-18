<?php
namespace Blocks;

/**
 * S3 source type class
 */
class S3AssetSourceType extends BaseAssetSourceType
{
	/**
	 * A list of predefined endpoints.
	 *
	 * @var array
	 */
	private static $_predefinedEndpoints = array(
		'US' => 's3.amazonaws.com',
		'EU' => 's3-eu-west-1.amazonaws.com'
	);

	/**
	 * @var \S3
	 */
	private $_s3;

	/**
	 * Construct the source type and set S3 instance.
	 */
	public function __construct()
	{
		$settings = $this->getSettings();
		$this->_s3 = new \S3($settings->keyId, $settings->secret);
	}

	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'Amazon S3';
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'keyId'      => array(AttributeType::String, 'required' => true),
			'secret'     => array(AttributeType::String, 'required' => true),
			'bucket'     => array(AttributeType::String, 'required' => true),
			'location'   => array(AttributeType::String, 'required' => true),
			'urlPrefix'  => array(AttributeType::String, 'required' => true),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/assetsourcetypes/S3/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Prepare the S3 connection for requests to this bucket.
	 */
	private function _prepareForRequests()
	{
		$settings = $this->getSettings();
		\S3::setAuth($settings->keyId, $settings->secret);

		$this->_s3->setEndpoint(static::getEndpointByLocation($settings->location));
	}

	/**
	 * Get bucket list with credentials.
	 *
	 * @param $keyId
	 * @param $secret
	 * @return array
	 * @throws Exception
	 */
	public static function getBucketList($keyId, $secret)
	{
		$s3 = new \S3($keyId, $secret);
		$buckets = @$s3->listBuckets();
		if (empty($buckets))
		{
			throw new Exception(Blocks::t("Credentials rejected by target host."));
		}

		$bucketList = array();
		foreach ($buckets as $bucket)
		{

			$location = $s3->getBucketLocation($bucket);

			$bucketList[] = array(
				'bucket' => $bucket,
				'location' => $location,
				'url_prefix' => 'http://'.static::getEndpointByLocation($location).'/'.$bucket.'/'
			);

		}
		return $bucketList;
	}

	/**
	 * Get a bucket's endpoint by location.
	 *
	 * @param $location
	 * @return string
	 */
	public static function getEndpointByLocation($location)
	{
		if (isset(static::$_predefinedEndpoints[$location]))
		{
			return static::$_predefinedEndpoints[$location];
		}
		return 's3-'.$location.'.amazonaws.com';
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 * @return array
	 */
	public function startIndex($sessionId)
	{

		$settings = $this->getSettings();
		$this->_prepareForRequests();

		$offset = 0;
		$total = 0;

		$fileList = $this->_s3->getBucket($settings->bucket);

		$fileList = array_filter($fileList, function ($value) {
			$path = $value['name'];
			$segments = explode('/', $path);
			foreach ($segments as $segment)
			{
				if (isset($segment[0]) && $segment[0] == '_')
				{
					return false;
				}
			}
			return true;
		});

		$bucketFolders = array();

		foreach ($fileList as $file)
		{
			if (!preg_match(AssetsHelper::IndexSkipItemsPattern, $file['name']))
			{
				// In S3, it's possible to have files in folders that don't exist. E.g. - one/two/three.jpg.
				// If folder "one" is empty, except for folder "two", then "one" won't show up in this list so we work around it.

				// Matches all paths with folders, except if folder is last or no folder at all.
				if (preg_match('/(.*\/).+$/', $file['name'], $matches))
				{
					$folders = explode('/', rtrim($matches[1], '/'));
					$basePath = '';
					foreach ($folders as $folder)
					{
						$basePath .= $folder .'/';

						// This is exactly the case referred to above
						if ( ! isset($bucketFolders[$basePath]))
						{
							$bucketFolders[$basePath] = true;
						}
					}
				}

				if (substr($file['name'], -1) == '/')
				{
					$bucketFolders[$file['name']] = true;
				}
				else
				{
					$indexEntry = array(
						'sourceId' => $this->model->id,
						'sessionId' => $sessionId,
						'offset' => $offset++,
						'uri' => $file['name'],
						'size' => $file['size']
					);
					blx()->assetIndexing->storeIndexEntry($indexEntry);
					$total++;
				}
			}
		}

		$indexedFolderIds = array();
		$indexedFolderIds[blx()->assetIndexing->ensureTopFolder($this->model)] = true;

		// Ensure folders are in the DB
		foreach ($bucketFolders as $fullPath => $nothing)
		{
			$folderId = $this->_ensureFolderByFulPath($fullPath);
			$indexedFolderIds[$folderId] = true;
		}

		$missingFolders = $this->_getMissingFolders($indexedFolderIds);

		return array('sourceId' => $this->model->id, 'total' => $total, 'missingFolders' => $missingFolders);
	}

	/**
	 * Process an indexing session.
	 *
	 * @param $sessionId
	 * @param $offset
	 * @return mixed
	 */
	public function processIndex($sessionId, $offset)
	{
		$indexEntryModel = blx()->assetIndexing->getIndexEntry($this->model->id, $sessionId, $offset);

		if (empty($indexEntryModel))
		{
			return false;
		}

		$uriPath = $indexEntryModel->uri;

		$fileModel = $this->_indexFile($uriPath);

		$this->_prepareForRequests();
		if ($fileModel)
		{
			$settings = $this->getSettings();

			blx()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;

			$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $uriPath);

			$targetPath = blx()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);

			if ($fileModel->kind == 'image' && $fileModel->dateModified != $fileInfo['time'] || !IOHelper::fileExists($targetPath))
			{
				$this->_s3->getObject($settings->bucket, $indexEntryModel->uri, $targetPath);
				clearstatcache();
				list ($fileModel->width, $fileModel->height) = getimagesize($targetPath);
			}

			$fileModel->dateModified = new DateTime('@'.$fileInfo['time']);

			blx()->assets->storeFile($fileModel);

			return $fileModel->id;
		}

		return false;
	}

	/**
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $filePath
	 * @param $fileName
	 * @return AssetFileModel
	 * @throws Exception
	 */
	protected function _insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName)
	{

		$fileName = IOHelper::cleanFilename($fileName);

		$extension = IOHelper::getExtension($fileName);

		if (! IOHelper::isExtensionAllowed($extension))
		{
			throw new Exception(Blocks::t('This file type is not allowed'));
		}

		$uriPath = $folder->fullPath.$fileName;

		$this->_prepareForRequests();
		$settings = $this->getSettings();
		$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $uriPath);

		if ($fileInfo)
		{
			/*$response = new AssetOperationResponseModel();
			$response->setResponse(AssetOperationResponseModel::StatusConflict);
			$response->setResponseDataItem('prompt', $this->_getUserPromptOptions($fileName));
			return $response;*/
			// TODO handle the conflict instead of just saving as new
			$targetPath = $folder->fullPath.$this->_getNameReplacement($folder, $fileName);
			if (!$targetPath)
			{
				throw new Exception(Blocks::t('Could not find a suitable replacement name for file'));
			}
			else
			{
				$uriPath = $targetPath;
			}
		}

		$this->_prepareForRequests();
		if (!$this->_s3->putObject(array('file' => $filePath), $this->getSettings()->bucket, $uriPath))
		{
			throw new Exception(Blocks::t('Could not copy file to target destination'));
		}

		/*$response = new AssetOperationResponseModel();
		$response->setResponse(AssetOperationResponseModel::StatusSuccess);
		$response->setResponseDataItem('file_path', $targetPath);
		return $response;*/
		return $uriPath;
	}

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel
	 * @return mixed
	 */
	public function getImageSourcePath(AssetFileModel $fileModel)
	{
		return blx()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);
	}

	/**
	 * Get the timestamp of when a file size was last modified.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $sizeHandle
	 * @return mixed
	 */
	public function getTimeSizeModified(AssetFileModel $fileModel, $sizeHandle)
	{
		$folder = $fileModel->getFolder();
		$path = $folder->fullPath.'_'.$sizeHandle.'/'.$fileModel->filename;
		$this->_prepareForRequests();
		$info = $this->_s3->getObjectInfo($this->getSettings()->bucket, $path);
		if (empty($info))
		{
			return false;
		}
		return new DateTime('@'.$info['time']);
	}

	/**
	* Put an image size for the File and handle using the provided path to the source image.
	*
	* @param AssetFileModel $fileModel
	* @param $handle
	* @param $sourceImage
	* @return mixed
	*/
	public function putImageSize(AssetFileModel $fileModel, $handle, $sourceImage)
	{
		$this->_prepareForRequests();
		$targetFile = rtrim($fileModel->getFolder()->fullPath, '/').'/_'.$handle.'/'.$fileModel->filename;
		return $this->_s3->putObject(array('file' => $sourceImage), $this->getSettings()->bucket, $targetFile);
	}

	/**
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $fileName
	 * @return mixed
	 */
	protected function _getNameReplacement(AssetFolderModel $folder, $fileName)
	{
		$this->_prepareForRequests();
		$fileList = $this->_s3->getBucket($this->getSettings()->bucket, $folder->fullPath);

		$fileNameParts = explode(".", $fileName);

		$extension = array_pop($fileNameParts);

		$fileNameStart = join(".", $fileNameParts) . '_';
		$index = 1;

		while ( isset($fileList[$folder->fullPath . $fileNameStart . $index . '.' . $extension]))
		{
			$index++;
		}

		return $fileNameStart . $index . '.' . $extension;
	}

}
