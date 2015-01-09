<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\enums\AssetConflictResolution;
use craft\app\enums\AttributeType;
use craft\app\enums\ElementType;
use craft\app\errors\Exception;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\IOHelper;
use craft\app\models\AssetFolder as AssetFolderModel;
use craft\app\web\UploadedFile;

/**
 * Assets fieldtype.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Assets extends BaseElementFieldType
{
	// Properties
	// =========================================================================

	/**
	 * The element type this field deals with.
	 *
	 * @var string $elementType
	 */
	protected $elementType = 'Asset';

	/**
	 * The JS class that should be initialized for the input.
	 *
	 * @var string|null $inputJsClass
	 */
	protected $inputJsClass = 'Craft.AssetSelectInput';

	/**
	 * Template to use for field rendering.
	 *
	 * @var string
	 */
	protected $inputTemplate = '_components/fieldtypes/Assets/input';

	/**
	 * Uploaded files that failed validation.
	 *
	 * @var array
	 */
	private $_failedFiles = [];

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// Create a list of folder options for the main Source setting, and source options for the upload location
		// settings.
		$folderOptions = [];
		$sourceOptions = [];

		foreach ($this->getElementType()->getSources() as $key => $source)
		{
			if (!isset($source['heading']))
			{
				$folderOptions[] = ['label' => $source['label'], 'value' => $key];
			}
		}

		foreach (Craft::$app->assetSources->getAllSources() as $source)
		{
			$sourceOptions[] = ['label' => $source->name, 'value' => $source->id];
		}

		$fileKindOptions = [];

		foreach (IOHelper::getFileKinds() as $value => $kind)
		{
			$fileKindOptions[] = ['value' => $value, 'label' => $kind['label']];
		}

		$namespace = Craft::$app->templates->getNamespace();
		$isMatrix = (strncmp($namespace, 'types[Matrix][blockTypes][', 26) === 0);

		return Craft::$app->templates->render('_components/fieldtypes/Assets/settings', [
			'folderOptions'     => $folderOptions,
			'sourceOptions'     => $sourceOptions,
			'targetLocaleField' => $this->getTargetLocaleFieldHtml(),
			'settings'          => $this->getSettings(),
			'type'              => $this->getName(),
			'fileKindOptions'   => $fileKindOptions,
			'isMatrix'          => $isMatrix,
		]);
	}

	/**
	 * @inheritDoc FieldTypeInterface::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		// See if we have uploaded file(s).
		$contentPostLocation = $this->getContentPostLocation();

		if ($contentPostLocation)
		{
			$uploadedFiles = UploadedFile::getInstancesByName($contentPostLocation);

			if (!empty($uploadedFiles))
			{
				// See if we have to validate against fileKinds
				$settings = $this->getSettings();

				if (isset($settings->restrictFiles) && !empty($settings->restrictFiles) && !empty($settings->allowedKinds))
				{
					$allowedExtensions = static::_getAllowedExtensions($settings->allowedKinds);
					$failedFiles = [];

					foreach ($uploadedFiles as $uploadedFile)
					{
						$extension = mb_strtolower(IOHelper::getExtension($uploadedFile->getName()));

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
				$targetFolderId = $this->_determineUploadFolderId($settings);

				if (!empty($targetFolderId))
				{
					foreach ($uploadedFiles as $file)
					{
						$tempPath = AssetsHelper::getTempFilePath($file->getName());
						move_uploaded_file($file->getTempName(), $tempPath);
						$response = Craft::$app->assets->insertFileByLocalPath($tempPath, $file->getName(), $targetFolderId);
						$fileIds[] = $response->getDataItem('fileId');
						IOHelper::deleteFile($tempPath, true);
					}

					if (is_array($value) && is_array($fileIds))
					{
						$fileIds = array_merge($value, $fileIds);
					}

					return $fileIds;
				}
			}
		}

		return parent::prepValueFromPost($value);
	}

	/**
	 * @inheritDoc FieldTypeInterface::onAfterElementSave()
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
		$handle = $this->model->handle;
		$elementFiles = $this->element->{$handle};

		if (is_object($elementFiles))
		{
			$elementFiles = $elementFiles->find();
		}

		if (is_array($elementFiles) && count($elementFiles))
		{

			$fileIds = [];

			foreach ($elementFiles as $elementFile)
			{
				$fileIds[] = $elementFile->id;
			}

			$settings = $this->getSettings();

			if ($this->getSettings()->useSingleFolder)
			{
				$targetFolderId = $this->_resolveSourcePathToFolderId(
					$settings->singleUploadLocationSource,
					$settings->singleUploadLocationSubpath);

				// Move all the files for single upload directories.
				$filesToMove = $fileIds;
			}
			else
			{
				// Find the files with temp sources and just move those.
				$criteria = [
					'id' => array_merge(['in'], $fileIds),
					'sourceId' => ':empty:'
				];

				$filesInTempSource = Craft::$app->elements->getCriteria(ElementType::Asset, $criteria)->find();
				$filesToMove = [];

				foreach ($filesInTempSource as $file)
				{
					$filesToMove[] = $file->id;
				}

				// If we have some files to move, make sure the folder exists.
				if (!empty($filesToMove))
				{
					$targetFolderId = $this->_resolveSourcePathToFolderId(
						$settings->defaultUploadLocationSource,
						$settings->defaultUploadLocationSubpath);
				}
			}

			if (!empty($filesToMove))
			{
				// Resolve all conflicts by keeping both
				$actions = array_fill(0, count($filesToMove), AssetConflictResolution::KeepBoth);
				Craft::$app->assets->moveFiles($filesToMove, $targetFolderId, '', $actions);
			}
		}

		parent::onAfterElementSave();
	}

	/**
	 * @inheritDoc FieldTypeInterface::validate()
	 *
	 * @param array $value
	 *
	 * @return true|string|array
	 */
	public function validate($value)
	{
		$errors = parent::validate($value);

		if (!is_array($errors))
		{
			$errors = [];
		}

		$settings = $this->getSettings();

		// Check if this field restricts files and if files are passed at all.
		if (isset($settings->restrictFiles) && !empty($settings->restrictFiles) && !empty($settings->allowedKinds) && is_array($value) && !empty($value))
		{
			$allowedExtensions = static::_getAllowedExtensions($settings->allowedKinds);

			foreach ($value as $fileId)
			{
				$file = Craft::$app->assets->getFileById($fileId);

				if ($file && !in_array(mb_strtolower(IOHelper::getExtension($file->filename)), $allowedExtensions))
				{
					$errors[] = Craft::t('"{filename}" is not allowed in this field.', ['filename' => $file->filename]);
				}
			}
		}

		foreach ($this->_failedFiles as $file)
		{
			$errors[] = Craft::t('"{filename}" is not allowed in this field.', ['filename' => $file->getName()]);
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
	 * @return mixed|null
	 */
	public function resolveSourcePath()
	{
		return $this->_determineUploadFolderId($this->getSettings());
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementFieldType::getAddButtonLabel()
	 *
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add an asset');
	}

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array_merge(parent::defineSettings(), [
			'useSingleFolder'              => AttributeType::Bool,
			'defaultUploadLocationSource'  => AttributeType::Number,
			'defaultUploadLocationSubpath' => AttributeType::String,
			'singleUploadLocationSource'   => AttributeType::Number,
			'singleUploadLocationSubpath'  => AttributeType::String,
			'restrictFiles'                => AttributeType::Bool,
			'allowedKinds'                 => AttributeType::Mixed,
		]);
	}

	/**
	 * @inheritDoc BaseElementFieldType::getInputSources()
	 *
	 * @throws Exception
	 * @return array
	 */
	protected function getInputSources()
	{
		// Look for the single folder setting
		$settings = $this->getSettings();

		if ($settings->useSingleFolder)
		{
			$folderId = $this->_determineUploadFolderId($settings);
			Craft::$app->getSession()->authorize('uploadToAssetSource:'.$folderId);
			$folderPath = 'folder:'.$folderId.':single';

			return [$folderPath];
		}

		$sources = [];

		// If it's a list of source IDs, we need to convert them to their folder counterparts
		if (is_array($settings->sources))
		{
			foreach ($settings->sources as $source)
			{
				if (strncmp($source, 'folder:', 7) === 0)
				{
					$sources[] = $source;
				}
			}
		}
		else if ($settings->sources == '*')
		{
			$sources = '*';
		}

		return $sources;
	}

	/**
	 * @inheritDoc BaseElementFieldType::getInputSelectionCriteria()
	 *
	 * @return array
	 */
	protected function getInputSelectionCriteria()
	{
		$settings = $this->getSettings();
		$allowedKinds = [];

		if (isset($settings->restrictFiles) && !empty($settings->restrictFiles) && !empty($settings->allowedKinds))
		{
			$allowedKinds = $settings->allowedKinds;
		}

		return ['kind' => $allowedKinds];
	}

	// Private Methods
	// =========================================================================

	/**
	 * Resolve a source path to it's folder ID by the source path and the matched source beginning.
	 *
	 * @param int    $sourceId
	 * @param string $subpath
	 *
	 * @throws Exception
	 * @return mixed
	 */
	private function _resolveSourcePathToFolderId($sourceId, $subpath)
	{
		$folder = Craft::$app->assets->findFolder([
			'sourceId' => $sourceId,
			'parentId' => ':empty:'
		]);

		// Do we have the folder?
		if (empty($folder))
		{
			throw new Exception (Craft::t('Cannot find the target folder.'));
		}

		// Prepare the path by parsing tokens and normalizing slashes.
		$subpath = trim($subpath, '/');
		$subpath = Craft::$app->templates->renderObjectTemplate($subpath, $this->element);
		$pathParts = explode('/', $subpath);

		foreach ($pathParts as &$part)
		{
			$part = IOHelper::cleanFilename($part);
		}

		$subpath = join('/', $pathParts);

		if (strlen($subpath))
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
			$folderCriteria = ['sourceId' => $sourceId, 'path' => $folder->path.$subpath];
			$existingFolder = Craft::$app->assets->findFolder($folderCriteria);
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
				$existingFolder = Craft::$app->assets->findFolder($folderCriteria);

				if (!$existingFolder)
				{
					$folderId = $this->_createSubFolder($currentFolder, $part);
					$existingFolder = Craft::$app->assets->getFolderById($folderId);
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
	 * @param $currentFolder
	 * @param $folderName
	 *
	 * @return mixed|null
	 */
	private function _createSubFolder($currentFolder, $folderName)
	{
		$response = Craft::$app->assets->createFolder($currentFolder->id, $folderName);

		if ($response->isError() || $response->isConflict())
		{
			// If folder doesn't exist in DB, but we can't create it, it probably exists on the server.
			$newFolder = new AssetFolderModel(
				[
					'parentId' => $currentFolder->id,
					'name' => $folderName,
					'sourceId' => $currentFolder->sourceId,
					'path' => trim($currentFolder->path.'/'.$folderName, '/').'/'
				]
			);
			$folderId = Craft::$app->assets->storeFolder($newFolder);

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
	 * @param $settings
	 *
	 * @throws Exception
	 * @return mixed|null
	 */
	private function _determineUploadFolderId($settings)
	{
		// If there's no dynamic tags in the set path, or if the element has already been saved, we con use the real
		// folder
		if (!empty($this->element->id)
			|| (!empty($settings->useSingleFolder) && strpos($settings->singleUploadLocationSubpath, '{') === false)
			|| (empty($settings->useSingleFolder) && strpos($settings->defaultUploadLocationSubpath, '{') === false)
		)
		{
			// Use the appropriate settings for folder determination
			if (empty($settings->useSingleFolder))
			{
				$folderId = $this->_resolveSourcePathToFolderId($settings->defaultUploadLocationSource, $settings->defaultUploadLocationSubpath);
			}
			else
			{
				$folderId = $this->_resolveSourcePathToFolderId($settings->singleUploadLocationSource, $settings->singleUploadLocationSubpath);
			}
		}
		else
		{
			// New element, so we default to User's upload folder for this field
			$userModel = Craft::$app->getUser()->getIdentity();

			$userFolder = Craft::$app->assets->getUserFolder($userModel);

			$folderName = 'field_'.$this->model->id;
			$elementFolder = Craft::$app->assets->findFolder(['parentId' => $userFolder->id, 'name' => $folderName]);

			if (!$elementFolder)
			{
				$folderId = $this->_createSubFolder($userFolder, $folderName);
			}
			else
			{
				$folderId = $elementFolder->id;
			}

			IOHelper::ensureFolderExists(Craft::$app->path->getAssetsTempSourcePath().$folderName);
		}

		return $folderId;
	}
}
