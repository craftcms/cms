<?php
namespace Craft;

/**
 * Assets fieldtype.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
 */
class AssetsFieldType extends BaseElementFieldType
{
	/**
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Asset';

	/**
	 * @var string|null $inputJsClass The JS class that should be initialized for the input.
	 */
	protected $inputJsClass = 'Craft.AssetSelectInput';

	/**
	 * Template to use for field rendering
	 * @var string
	 */
	protected $inputTemplate = '_components/fieldtypes/Assets/input';

	/**
	 * Uploaded files that failed validation.
	 *
	 * @var array
	 */
	private $_failedFiles = array();

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add an asset');
	}

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array_merge(parent::defineSettings(), array(
			'useSingleFolder'              => AttributeType::Bool,
			'defaultUploadLocationSource'  => AttributeType::Number,
			'defaultUploadLocationSubpath' => AttributeType::String,
			'singleUploadLocationSource'   => AttributeType::Number,
			'singleUploadLocationSubpath'  => AttributeType::String,
			'restrictFiles'                => AttributeType::Bool,
			'allowedKinds'                 => AttributeType::Mixed,
		));
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// Create a list of folder options for the main Source setting, and source options for the upload location settings.
		$folderOptions = array();
		$sourceOptions = array();

		foreach ($this->getElementType()->getSources() as $key => $source)
		{
			if (!isset($source['heading']))
			{
				$folderOptions[] = array('label' => $source['label'], 'value' => $key);
			}
		}

		foreach (craft()->assetSources->getAllSources() as $source)
		{
			$sourceOptions[] = array('label' => $source->name, 'value' => $source->id);
		}

		$fileKindOptions = array();

		foreach (IOHelper::getFileKinds() as $value => $kind)
		{
			$fileKindOptions[] = array('value' => $value, 'label' => $kind['label']);
		}

		return craft()->templates->render('_components/fieldtypes/Assets/settings', array(
			'folderOptions'     => $folderOptions,
			'sourceOptions'     => $sourceOptions,
			'targetLocaleField' => $this->getTargetLocaleFieldHtml(),
			'settings'          => $this->getSettings(),
			'type'              => $this->getName(),
			'fileKindOptions'   => $fileKindOptions,
		));
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $value
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

					$failedFiles = array();
					foreach ($uploadedFiles as $uploadedFile)
					{
						$extension = IOHelper::getExtension($uploadedFile->getName());
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

				// If we got here either there are no restrictions or all files are valid
				// So let's turn them into Assets
				$fileIds = array();


				$targetFolderId = $this->_determineUploadFolderId($settings);
				if (!empty($targetFolderId))
				{
					foreach ($uploadedFiles as $file)
					{
						$tempPath = AssetsHelper::getTempFilePath($file->getName());
						move_uploaded_file($file->getTempName(), $tempPath);
						$fileIds[] = craft()->assets->insertFileByLocalPath($tempPath, $file->getName(), $targetFolderId);
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
	 * Handle file moves between folders for dynamic single folder settings.
	 */
	public function onAfterElementSave()
	{
		$handle = $this->model->handle;
		$filesToMove = $this->element->getContent()->{$handle};

		if (is_array($filesToMove) && count($filesToMove))
		{

			$settings = $this->getSettings();
			if ($this->getSettings()->useSingleFolder)
			{
				$targetFolderId = $this->_resolveSourcePathToFolderId($settings->singleUploadLocationSource, $settings->singleUploadLocationSubpath);
			}
			else
			{
				$targetFolderId = $this->_resolveSourcePathToFolderId($settings->defaultUploadLocationSource, $settings->defaultUploadLocationSubpath);

				$criteria = array(
					'id' => array_merge(array('in'), $filesToMove),
					'sourceId' => ':empty:'
				);
				$criteria = craft()->elements->getCriteria(ElementType::Asset, $criteria);

				$files = $criteria->find();
				$filesToMove = array();

				foreach ($files as $file)
				{
					$filesToMove[] = $file->id;
				}
			}

			if (!empty($filesToMove))
			{
				// Resolve all conflicts by keeping both
				$actions = array_fill(0, count($filesToMove), AssetsHelper::ActionKeepBoth);
				craft()->assets->moveFiles($filesToMove, $targetFolderId, '', $actions);
			}
		}

		parent::onAfterElementSave();
	}

	/**
	 * Validates the value.
	 *
	 * Returns 'true' or any custom validation errors.
	 *
	 * @param array $value
	 * @return true|string|array
	 */
	public function validate($value)
	{
		$errors = parent::validate($value);

		if (!is_array($errors))
		{
			$errors = array();
		}

		$settings = $this->getSettings();

		// Check if this field restricts files and if files are passed at all.
		if (isset($settings->restrictFiles) && !empty($settings->restrictFiles) && !empty($settings->allowedKinds) && is_array($value) && !empty($value))
		{
			$allowedExtensions = static::_getAllowedExtensions($settings->allowedKinds);
			foreach ($value as $fileId)
			{
				$file = craft()->assets->getFileById($fileId);
				if ($file && !in_array(IOHelper::getExtension($file->filename), $allowedExtensions))
				{
					$errors[] = Craft::t('"{filename}" is not allowed in this field.', array('filename' => $file->filename));
				}
			}
		}

		foreach ($this->_failedFiles as $file)
		{
			$errors[] = Craft::t('"{filename}" is not allowed in this field.', array('filename' => $file->getName()));
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

	/**
	 * Returns an array of the source keys the field should be able to select elements from.
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
			$folderPath = 'folder:'.$this->_determineUploadFolderId($settings).':single';

			return array($folderPath);
		}

		$sources = array();

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
	 * Returns any additional criteria parameters limiting which elements the field should be able to select.
	 *
	 * @return array
	 */
	protected function getInputSelectionCriteria()
	{
		$settings = $this->getSettings();
		$allowedKinds = array();

		if (isset($settings->restrictFiles) && !empty($settings->restrictFiles) && !empty($settings->allowedKinds))
		{
			$allowedKinds = $settings->allowedKinds;
		}

		return array('kind' => $allowedKinds);
	}

	/**
	 * Resolve a source path to it's folder ID by the source path and the matched source beginning.
	 *
	 * @param int $sourceId
	 * @param string $subpath
	 *
	 * @throws Exception
	 * @return mixed
	 */
	private function _resolveSourcePathToFolderId($sourceId, $subpath)
	{
		$folder = craft()->assets->findFolder(array(
			'sourceId' => $sourceId,
			'parentId' => ':empty:'
		));

		// Do we have the folder?
		if (empty($folder))
		{
			throw new Exception (Craft::t('Cannot find the target folder.'));
		}

		// Prepare the path by parsing tokens and normalizing slashes.
		$subpath = trim($subpath, '/');
		$subpath = craft()->templates->renderObjectTemplate($subpath, $this->element);
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
			$folderCriteria = array('sourceId' => $sourceId, 'path' => $folder->path . $subpath);
			$existingFolder = craft()->assets->findFolder($folderCriteria);
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
				$folderCriteria = array('parentId' => $currentFolder->id, 'name' => $part);
				$existingFolder = craft()->assets->findFolder($folderCriteria);
				if (!$existingFolder)
				{
					$folderId = $this->_createSubFolder($currentFolder, $part);
					$existingFolder = craft()->assets->getFolderById($folderId);
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
	 * @return mixed|null
	 */
	private function _createSubFolder($currentFolder, $folderName)
	{
		$response = craft()->assets->createFolder($currentFolder->id, $folderName);

		if ($response->isError() || $response->isConflict())
		{
			// If folder doesn't exist in DB, but we can't create it, it probably exists on the server.
			$newFolder = new AssetFolderModel(
				array(
					'parentId' => $currentFolder->id,
					'name' => $folderName,
					'sourceId' => $currentFolder->sourceId,
					'path' => trim($currentFolder->path . '/' . $folderName, '/') . '/'
				)
			);
			$folderId = craft()->assets->storeFolder($newFolder);
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
	 * @return array
	 */
	private function _getAllowedExtensions($allowedKinds)
	{
		if (!is_array($allowedKinds))
		{
			return array();
		}

		$extensions = array();
		$allKinds = IOHelper::getFileKinds();

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
		// If there's no dynamic tags in the set path, or if the element has already been saved, we con use the real folder
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
			$userModel = craft()->userSession->getUser();

			$userFolder = craft()->assets->getUserFolder($userModel);

			$folderName = 'field_' . $this->model->id;
			$elementFolder = craft()->assets->findFolder(array('parentId' => $userFolder->id, 'name' => $folderName));

			if (!$elementFolder)
			{
				$folderId = $this->_createSubFolder($userFolder, $folderName);
			}
			else
			{
				$folderId = $elementFolder->id;
			}

			IOHelper::ensureFolderExists(craft()->path->getAssetsTempSourcePath().$folderName);
		}

		return $folderId;
	}
}
