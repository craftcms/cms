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
		$this->_s3 = new \S3($this->getSettings()->keyId, $settings->secret);
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
		$this->_setS3Credentials($settings->keyId, $settings->secret);
		$this->_s3->setEndpoint(static::getEndpointByLocation($settings->location));

		$offset = 0;
		$total = 0;

		$fileList = $this->_s3->getBucket($settings->bucket);

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

		if ($fileModel)
		{
			$settings = $this->getSettings();
			static::_setS3Credentials($settings->keyId, $settings->secret);
			blx()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;

			$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $uriPath);

			if ($fileModel->kind == 'image' && $fileModel->dateModified != $fileInfo['time'])
			{

				$targetPath = blx()->path->getAssetsImageSourcePath() . $fileModel->filename;
				$this->_s3->getObject($settings->bucket, $indexEntryModel->uri, $targetPath);
				list ($fileModel->width, $fileModel->height) = getimagesize($targetPath);
			}

			$fileModel->dateModified = $fileInfo['time'];

			blx()->assets->storeFile($fileModel);
		}


		return true;
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
		// TODO: Implement _insertFileInFolder() method.
	}

	/**
	 * Set S3 credentials.
	 *
	 * @param $keyId
	 * @param $secretKey
	 */
	private function _setS3Credentials($keyId, $secret)
	{
		\S3::setAuth($keyId, $secret);
	}

}
