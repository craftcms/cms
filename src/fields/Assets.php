<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\elements\Asset;
use craft\app\elements\db\AssetQuery;
use craft\app\errors\Exception;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use craft\app\models\VolumeFolder;
use craft\app\web\UploadedFile;

/**
 * Assets represents an Assets field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Assets extends BaseRelationField
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Assets');
	}

	/**
	 * @inheritdoc
	 * @return Asset
	 */
	protected static function elementType()
	{
		return Asset::className();
	}

	// Properties
	// =========================================================================

	/**
	 * @var boolean Whether related assets should be limited to a single folder
	 */
	public $useSingleFolder;

	/**
	 * @var integer The asset source ID that files should be uploaded to by default (only used if [[useSingleFolder]] is false)
	 */
	public $defaultUploadLocationSource;

	/**
	 * @var string The subpath that files should be uploaded to by default (only used if [[useSingleFolder]] is false)
	 */
	public $defaultUploadLocationSubpath;

	/**
	 * @var integer The asset source ID that files should be restricted to (only used if [[useSingleFolder]] is true)
	 */
	public $singleUploadLocationSource;

	/**
	 * @var string The subpath that files should be restricted to (only used if [[useSingleFolder]] is true)
	 */
	public $singleUploadLocationSubpath;

	/**
	 * @var boolean Whether the available assets should be restricted to [[allowedKinds]]
	 */
	public $restrictFiles;

	/**
	 * @var array The file kinds that the field should be restricted to (only used if [[restrictFiles]] is true)
	 */
	public $allowedKinds;

	/**
	 * @inheritdoc
	 */
	protected $inputJsClass = 'Craft.AssetSelectInput';

	/**
	 * @inheritdoc
	 */
	protected $inputTemplate = '_components/fieldtypes/Assets/input';

	/**
	 * Uploaded files that failed validation.
	 *
	 * @var UploadedFile[]
	 */
	private $_failedFiles = [];

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getAddButtonLabel()
	{
		return Craft::t('app', 'Add an asset');
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		// Create a list of folder options for the main Source setting, and source options for the upload location
		// settings.
		$folderOptions = [];
		$sourceOptions = [];

		$class = self::elementType();

		foreach ($class::getSources() as $key => $source)
		{
			if (!isset($source['heading']))
			{
				$folderOptions[] = ['label' => $source['label'], 'value' => $key];
			}
		}

		foreach (Craft::$app->getVolumes()->getAllVolumes() as $source)
		{
			$sourceOptions[] = ['label' => $source->name, 'value' => $source->id];
		}

		$fileKindOptions = [];

		foreach (IOHelper::getFileKinds() as $value => $kind)
		{
			$fileKindOptions[] = ['value' => $value, 'label' => $kind['label']];
		}

		$namespace = Craft::$app->getView()->getNamespace();
		$isMatrix = (strncmp($namespace, 'types[Matrix][blockTypes][', 26) === 0);

		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Assets/settings', [
			'folderOptions'     => $folderOptions,
			'sourceOptions'     => $sourceOptions,
			'targetLocaleField' => $this->getTargetLocaleFieldHtml(),
			'field'             => $this,
			'displayName'       => self::displayName(),
			'fileKindOptions'   => $fileKindOptions,
			'isMatrix'          => $isMatrix,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function afterElementSave(ElementInterface $element)
	{
		$value = $this->getElementValue($element);

		if ($value instanceof AssetQuery)
		{
			$value = $value->all();
		}

		if (is_array($value) && count($value))
		{
			$fileIds = [];

			foreach ($value as $elementFile)
			{
				$fileIds[] = $elementFile->id;
			}

			if ($this->useSingleFolder)
			{
				$targetFolderId = $this->_resolveSourcePathToFolderId(
					$this->singleUploadLocationSource,
					$this->singleUploadLocationSubpath,
					$element
				);

				// Move all the files for single upload directories.
				$filesToMove = $fileIds;
			}
			else
			{
				// Find the files with temp sources and just move those.
				$criteria = [
					'id' => array_merge(['in'], $fileIds),
					'volumeId' => ':empty:'
				];

				$filesInTempSource = Asset::find()->configure($criteria)->all();
				$filesToMove = [];

				foreach ($filesInTempSource as $file)
				{
					$filesToMove[] = $file->id;
				}

				// If we have some files to move, make sure the folder exists.
				if (!empty($filesToMove))
				{
					$targetFolderId = $this->_resolveSourcePathToFolderId(
						$this->defaultUploadLocationSource,
						$this->defaultUploadLocationSubpath,
						$element
					);
				}
			}

			if (!empty($filesToMove))
			{
				// Resolve all conflicts by keeping both
				$actions = array_fill(0, count($filesToMove), AssetConflictResolution::KeepBoth);
				Craft::$app->getAssets()->moveFiles($filesToMove, $targetFolderId, '', $actions);
			}
		}

		parent::afterElementSave($element);
	}

	/**
	 * @inheritdoc
	 */
	function validateValue($value, $element)
	{
		$errors = parent::validate($value);

		if (!is_array($errors))
		{
			$errors = [];
		}

		// Check if this field restricts files and if files are passed at all.
		if (isset($this->restrictFiles) && !empty($this->restrictFiles) && !empty($this->allowedKinds) && is_array($value) && !empty($value))
		{
			$allowedExtensions = $this->_getAllowedExtensions($this->allowedKinds);

			foreach ($value as $fileId)
			{
				$file = Craft::$app->getAssets()->getFileById($fileId);

				if ($file && !in_array(mb_strtolower(IOHelper::getExtension($file->filename)), $allowedExtensions))
				{
					$errors[] = Craft::t('app', '"{filename}" is not allowed in this field.', ['filename' => $file->filename]);
				}
			}
		}

		foreach ($this->_failedFiles as $file)
		{
			$errors[] = Craft::t('app', '"{filename}" is not allowed in this field.', ['filename' => $file->name]);
		}

		if ($errors)
		{
			return $errors;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Resolve source path for uploading for this field.
	 *
	 * @param ElementInterface|Element|null $element
	 * @return mixed
	 */
	public function resolveDynamicPath($element)
	{
		return $this->_determineUploadFolderId($element);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function prepareValueBeforeSave($value, $element)
	{
		// See if we have uploaded file(s).
		$contentPostLocation = $this->getContentPostLocation($element);

		if ($contentPostLocation)
		{
			$uploadedFiles = UploadedFile::getInstancesByName($contentPostLocation);

			if (!empty($uploadedFiles))
			{
				// See if we have to validate against fileKinds
				if (isset($this->restrictFiles) && !empty($this->restrictFiles) && !empty($this->allowedKinds))
				{
					$allowedExtensions = $this->_getAllowedExtensions($this->allowedKinds);
					$failedFiles = [];

					foreach ($uploadedFiles as $uploadedFile)
					{
						$extension = mb_strtolower(IOHelper::getExtension($uploadedFile->name));

						if (!in_array($extension, $allowedExtensions))
						{
							$failedFiles[] = $uploadedFile;
						}
					}

					// If any files failed the validation, make a note of it.
					if (!empty($failedFiles))
					{
						$this->_failedFiles = $failedFiles;
						return true;
					}
				}

				// If we got here either there are no restrictions or all files are valid so let's turn them into Assets
				$fileIds = [];
				$targetFolderId = $this->_determineUploadFolderId($element);

				if (!empty($targetFolderId))
				{
					foreach ($uploadedFiles as $file)
					{
						$tempPath = AssetsHelper::getTempFilePath($file->name);
						move_uploaded_file($file->tempName, $tempPath);
						$response = Craft::$app->getAssets()->insertFileByLocalPath($tempPath, $file->name, $targetFolderId);
						$fileIds[] = $response->getDataItem('fileId');
						IOHelper::deleteFile($tempPath, true);
					}

					if (is_array($value) && is_array($fileIds))
					{
						$fileIds = array_merge($value, $fileIds);
					}

					// Make it look like the actual POST data contained these file IDs as well,
					// so they make it into entry draft/version data
					$element->setRawPostContent($this->handle, $fileIds);

					return $fileIds;
				}
			}
		}

		return parent::prepareValueBeforeSave($value, $element);
	}

	/**
	 * @inheritdoc
	 */
	protected function getInputSources($element)
	{
		// Look for the single folder setting
		if ($this->useSingleFolder)
		{
			$folderId = $this->_determineUploadFolderId($element);
			Craft::$app->getSession()->authorize('uploadToVolume:'.$folderId);
			$folderPath = 'folder:'.$folderId.':single';

			return [$folderPath];
		}

		$sources = [];

		// If it's a list of source IDs, we need to convert them to their folder counterparts
		if (is_array($this->sources))
		{
			foreach ($this->sources as $source)
			{
				if (strncmp($source, 'folder:', 7) === 0)
				{
					$sources[] = $source;
				}
			}
		}
		else if ($this->sources == '*')
		{
			$sources = '*';
		}

		return $sources;
	}

	/**
	 * @inheritdoc
	 */
	protected function getInputSelectionCriteria()
	{
		$allowedKinds = [];

		if (isset($this->restrictFiles) && !empty($this->restrictFiles) && !empty($this->allowedKinds))
		{
			$allowedKinds = $this->allowedKinds;
		}

		return ['kind' => $allowedKinds];
	}

	// Private Methods
	// =========================================================================

	/**
	 * Resolve a source path to it's folder ID by the source path and the matched source beginning.
	 *
	 * @param int                      $volumeId
	 * @param string                   $subpath
	 * @param ElementInterface|Element $element
	 *
	 * @throws Exception
	 * @return mixed
	 */
	private function _resolveSourcePathToFolderId($volumeId, $subpath, $element)
	{
		$folder = Craft::$app->getAssets()->findFolder([
			'volumeId' => $volumeId,
			'parentId' => ':empty:'
		]);

		// Do we have the folder?
		if (empty($folder))
		{
			throw new Exception (Craft::t('app', 'Cannot find the target folder.'));
		}

		// Prepare the path by parsing tokens and normalizing slashes.
		$subpath = trim($subpath, '/');
		$subpath = Craft::$app->getView()->renderObjectTemplate($subpath, $element);
		$pathParts = explode('/', $subpath);

		foreach ($pathParts as &$part)
		{
			$part = IOHelper::cleanFilename($part, Craft::$app->getConfig()->get('convertFilenamesToAscii'));
		}

		$subpath = join('/', $pathParts);

		if (StringHelper::length($subpath))
		{
			$subpath = $subpath.'/';
		}

		// Let's see if the folder already exists.
		if (empty($subpath))
		{
			$existingFolder = $folder;
		}
		else
		{
			$folderCriteria = ['volumeId' => $volumeId, 'path' => $folder->path.$subpath];
			$existingFolder = Craft::$app->getAssets()->findFolder($folderCriteria);
		}


		// No dice, go over each folder in the path and create it if it's missing.
		if (!$existingFolder)
		{
			$parts = explode('/', $subpath);

			// Now make sure that every folder in the path exists.
			$currentFolder = $folder;

			foreach ($parts as $part)
			{
				if (empty($part))
				{
					continue;
				}

				$folderCriteria = ['parentId' => $currentFolder->id, 'name' => $part];
				$existingFolder = Craft::$app->getAssets()->findFolder($folderCriteria);

				if (!$existingFolder)
				{
					$folderId = $this->_createSubFolder($currentFolder, $part);
					$existingFolder = Craft::$app->getAssets()->getFolderById($folderId);
				}

				$currentFolder = $existingFolder;
			}
		}
		else
		{
			$currentFolder = $existingFolder;
		}

		return $currentFolder->id;
	}

	/**
	 * Create a subfolder in a folder by it's name.
	 *
	 * @param VolumeFolder $currentFolder
	 * @param string $folderName
	 *
	 * @return mixed|null
	 */
	private function _createSubFolder($currentFolder, $folderName)
	{
		$response = Craft::$app->getAssets()->createFolder($currentFolder->id, $folderName);

		if ($response->isError() || $response->isConflict())
		{
			// If folder doesn't exist in DB, but we can't create it, it probably exists on the server.
			$newFolder = new VolumeFolder(
				[
					'parentId' => $currentFolder->id,
					'name' => $folderName,
					'volumeId' => $currentFolder->volumeId,
					'path' => trim($currentFolder->path.'/'.$folderName, '/').'/'
				]
			);
			$folderId = Craft::$app->getAssets()->storeFolder($newFolder);

			return $folderId;
		}
		else
		{
			$folderId = $response->getDataItem('folderId');

			return $folderId;
		}
	}

	/**
	 * Get a list of allowed extensions for a list of file kinds.
	 *
	 * @param array $allowedKinds
	 *
	 * @return array
	 */
	private function _getAllowedExtensions($allowedKinds)
	{
		if (!is_array($allowedKinds))
		{
			return [];
		}

		$extensions = [];
		$allKinds   = IOHelper::getFileKinds();

		foreach ($allowedKinds as $allowedKind)
		{
			$extensions = array_merge($extensions, $allKinds[$allowedKind]['extensions']);
		}

		return $extensions;
	}

	/**
	 * Determine an upload folder id by looking at the settings and whether Element this field belongs to is new or not.
	 *
	 * @param ElementInterface|Element|null $element
	 * @throws Exception
	 * @return mixed|null
	 */
	private function _determineUploadFolderId($element)
	{
		// If there's no dynamic tags in the set path, or if the element has already been saved, we con use the real
		// folder
		if (!empty($element->id)
			|| (!empty($this->useSingleFolder) && !StringHelper::contains($this->singleUploadLocationSubpath, '{'))
			|| (empty($this->useSingleFolder) && !StringHelper::contains($this->defaultUploadLocationSubpath, '{'))
		)
		{
			// Use the appropriate settings for folder determination
			if (empty($this->useSingleFolder))
			{
				$folderId = $this->_resolveSourcePathToFolderId(
					$this->defaultUploadLocationSource,
					$this->defaultUploadLocationSubpath,
					$element
				);
			}
			else
			{
				$folderId = $this->_resolveSourcePathToFolderId(
					$this->singleUploadLocationSource,
					$this->singleUploadLocationSubpath,
					$element
				);
			}
		}
		else
		{
			// New element, so we default to User's upload folder for this field
			$userModel = Craft::$app->getUser()->getIdentity();

			$userFolder = Craft::$app->getAssets()->getUserFolder($userModel);

			$folderName = 'field_'.$this->id;
			$elementFolder = Craft::$app->getAssets()->findFolder(['parentId' => $userFolder->id, 'name' => $folderName]);

			if (!$elementFolder)
			{
				$folderId = $this->_createSubFolder($userFolder, $folderName);
			}
			else
			{
				$folderId = $elementFolder->id;
			}

			IOHelper::ensureFolderExists(Craft::$app->getPath()->getAssetsTempSourcePath().'/'.$folderName);
		}

		return $folderId;
	}
}
