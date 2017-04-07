<?php
namespace Craft;

/**
 * Assets fieldtype.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class AssetsFieldType extends BaseElementFieldType
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
	 * Whether to allow the “Large Thumbnails” view mode.
	 *
	 * @var bool $allowLargeThumbsView
	 */
	protected $allowLargeThumbsView = true;

	/**
	 * Template to use for field rendering.
	 *
	 * @var string
	 */
	protected $inputTemplate = '_components/fieldtypes/Assets/input';

	/**
	 * The JS class that should be initialized for the input.
	 *
	 * @var string|null $inputJsClass
	 */
	protected $inputJsClass = 'Craft.AssetSelectInput';

	/**
	 * Uploaded files that failed validation.
	 *
	 * @var array
	 */
	private $_failedFiles = array();

    /**
     * Results of the prepValueFromPost method when it was run.
     *
     * @var array
     */
	private $_prepValueFromPostResults = [];

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// Create a list of folder options for the main Source setting, and source options for the upload location
		// settings.
		$folderOptions = array();
		$sourceOptions = array();

		foreach ($this->getElementType()->getSources('settings') as $key => $source)
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

		$namespace = craft()->templates->getNamespace();
		$isMatrix = (strncmp($namespace, 'types[Matrix][blockTypes][', 26) === 0);

		return craft()->templates->render('_components/fieldtypes/Assets/settings', array(
			'allowLimit'            => $this->allowLimit,
			'folderOptions'         => $folderOptions,
			'sourceOptions'         => $sourceOptions,
			'targetLocaleFieldHtml' => $this->getTargetLocaleFieldHtml(),
			'viewModeFieldHtml'     => $this->getViewModeFieldHtml(),
			'settings'              => $this->getSettings(),
			'defaultSelectionLabel' => $this->getAddButtonLabel(),
			'type'                  => $this->getName(),
			'fileKindOptions'       => $fileKindOptions,
			'isMatrix'              => $isMatrix,
		));
	}

	/**
	 * @inheritDoc IFieldType::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 *
	 * @return string
	 */
	public function getInputHtml($name, $criteria)
	{
		try
		{
			return parent::getInputHtml($name, $criteria);
		}
		catch (InvalidSubpathException $e)
		{
			return '<p class="warning">' .
				'<span data-icon="alert"></span> ' .
				Craft::t('This field’s target subfolder path is invalid: {path}', array('path' => '<code>'.$this->getSettings()->singleUploadLocationSubpath.'</code>')) .
				'</p>';
		}
		catch (InvalidSourceException $e)
		{
			$message = $this->getSettings()->useSingleFolder ? Craft::t('This field’s single upload location Assets Source is missing') : Craft::t('This field’s default upload location Assets Source is missing');
			return '<p class="warning">' .
			'<span data-icon="alert"></span> ' .
			$message .
			'</p>';
		}
	}

	/**
	 * @inheritDoc IFieldType::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		if (
			($hash = $this->element ? spl_object_hash($this->element) : null) &&
			isset($this->_prepValueFromPostResults[$hash])
		)
		{
			return $this->_prepValueFromPostResults[$hash];
		}

		$dataFiles = array();

		// Grab data strings
		if (isset($value['data']) && is_array($value['data']))
		{
			foreach ($value['data'] as $index => $dataString)
			{
				if (preg_match('/^data:(?<type>[a-z0-9]+\/[a-z0-9]+);base64,(?<data>.+)/i', $dataString, $matches))
				{
					$type = $matches['type'];
					$data = base64_decode($matches['data']);

					if (!$data)
					{
						continue;
					}

					if (!empty($value['filenames'][$index]))
					{
						$filename = $value['filenames'][$index];
					}
					else
					{
						$extension = FileHelper::getExtensionByMimeType($type);
						$filename = 'Uploaded file.'.$extension;
					}

					$dataFiles[] = array(
						'filename' => $filename,
						'data' => $data
					);
				}
			}
		}

		// Remove these so they don't interfere.
		if (isset($value['data']) && isset($value['filenames']))
		{
			unset($value['data'], $value['filenames']);
		}

		$uploadedFiles = array();

		// See if we have uploaded file(s).
		$contentPostLocation = $this->getContentPostLocation();

		if ($contentPostLocation) {
			$files = UploadedFile::getInstancesByName($contentPostLocation);

			foreach ($files as $file)
			{
				$uploadedFiles[] = array(
					'filename' => $file->getName(),
					'location' => $file->getTempName()
				);
			}
		}

		// See if we have to validate against fileKinds
		$settings = $this->getSettings();

		$allowedExtensions = false;

		if (isset($settings->restrictFiles) && !empty($settings->restrictFiles) && !empty($settings->allowedKinds))
		{
			$allowedExtensions = static::_getAllowedExtensions($settings->allowedKinds);
		}

		if (is_array($allowedExtensions))
		{
			foreach ($dataFiles as $file)
			{
				$extension = StringHelper::toLowerCase(IOHelper::getExtension($file['filename']));

				if (!in_array($extension, $allowedExtensions))
				{
					$this->_failedFiles[] = $file['filename'];
				}
			}

			foreach ($uploadedFiles as $file)
			{
				$extension = StringHelper::toLowerCase(IOHelper::getExtension($file['filename']));

				if (!in_array($extension, $allowedExtensions))
				{
					$this->_failedFiles[] = $file['filename'];
				}
			}
		}

		if (!empty($this->_failedFiles))
		{
			return true;
		}

		// If we got here either there are no restrictions or all files are valid so let's turn them into Assets
		// Unless there are no files at all.
		if (empty($value) && empty($dataFiles) && empty($uploadedFiles))
		{
			return array();
		}

		if (empty($value))
		{
			$value = array();
		}

		$fileIds = array();

		if (!empty($dataFiles) || !empty($uploadedFiles))
		{
			$targetFolderId = $this->_determineUploadFolderId($settings);

			foreach ($dataFiles as $file)
			{
				$tempPath = AssetsHelper::getTempFilePath($file['filename']);
				IOHelper::writeToFile($tempPath, $file['data']);
				$response = craft()->assets->insertFileByLocalPath($tempPath, $file['filename'], $targetFolderId);
				$fileIds[] = $response->getDataItem('fileId');
				IOHelper::deleteFile($tempPath, true);
			}

			foreach ($uploadedFiles as $file)
			{
				$tempPath = AssetsHelper::getTempFilePath($file['filename']);
				move_uploaded_file($file['location'], $tempPath);
				$response = craft()->assets->insertFileByLocalPath($tempPath, $file['filename'], $targetFolderId);
				$fileIds[] = $response->getDataItem('fileId');
				IOHelper::deleteFile($tempPath, true);
			}
		}

		$fileIds = array_merge($value, $fileIds);

		// Make it look like the actual POST data contained these file IDs as well,
		// so they make it into entry draft/version data
		$this->element->setRawPostContent($this->model->handle, $fileIds);

		if ($hash)
		{
			$this->_prepValueFromPostResults[$hash] = $fileIds;
		}

		return $fileIds;
	}

	/**
	 * @inheritDoc IFieldType::onAfterElementSave()
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
		$handle = $this->model->handle;
		$elementFiles = $this->element->{$handle};

		if ($elementFiles instanceof ElementCriteriaModel)
		{
			$elementFiles = $elementFiles->find();
		}

		if (is_array($elementFiles) && !empty($elementFiles))
		{
			$filesToMove = array();
			$settings = $this->getSettings();
			$targetFolderId = $this->_determineUploadFolderId($settings);

			if ($settings->useSingleFolder)
			{
				// Move only the files with a changed folder ID.
				foreach ($elementFiles as $elementFile)
				{
					if ($targetFolderId != $elementFile->folderId)
					{
						$filesToMove[] = $elementFile->id;
					}
				}
			}
			else
			{
				$fileIds = array();

				foreach ($elementFiles as $elementFile)
				{
					$fileIds[] = $elementFile->id;
				}

				// Find the files with temp sources and just move those.
				$criteria =array(
					'id' => array_merge(array('in'), $fileIds),
					'sourceId' => ':empty:'
				);

				$filesInTempSource = craft()->elements->getCriteria(ElementType::Asset, $criteria)->find();
				$filesToMove = array();

				foreach ($filesInTempSource as $file)
				{
					$filesToMove[] = $file->id;
				}
			}

			if (!empty($filesToMove))
			{
				// Resolve all conflicts by keeping both
				$actions = array_fill(0, count($filesToMove), AssetConflictResolution::KeepBoth);
				craft()->assets->moveFiles($filesToMove, $targetFolderId, '', $actions);
			}
		}

		parent::onAfterElementSave();
	}

	/**
	 * @inheritDoc IFieldType::validate()
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

				if ($file && !in_array(mb_strtolower(IOHelper::getExtension($file->filename)), $allowedExtensions))
				{
					$errors[] = Craft::t('"{filename}" is not allowed in this field.', array('filename' => $file->filename));
				}
			}
		}

		foreach ($this->_failedFiles as $file)
		{
			$errors[] = Craft::t('"{filename}" is not allowed in this field.', array('filename' => $file));
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
		return $this->_determineUploadFolderId($this->getSettings(), true);
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
	 * @inheritDoc BaseElementFieldType::getInputSources()
	 *
	 * @throws Exception
	 * @return array
	 */
	protected function getInputSources()
	{
		$settings = $this->getSettings();

		// Authorize for the single folder and the default upload folder, whichever was chosen.
		$folderId = $this->_determineUploadFolderId($settings, false);
		craft()->userSession->authorize('uploadToAssetSource:'.$folderId);

		if ($settings->useSingleFolder)
		{
			$folderPath = 'folder:'.$folderId.':single';
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
	 * @inheritDoc BaseElementFieldType::getInputSelectionCriteria()
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

	// Private Methods
	// =========================================================================

	/**
	 * Resolve a source path to it's folder ID by the source path and the matched source beginning.
	 *
	 * @param int    $sourceId
	 * @param string $subpath
	 * @param bool   $createDynamicFolders whether missing folders should be created in the process
	 *
	 * @throws Exception
	 * @return mixed
	 */
	private function _resolveSourcePathToFolderId($sourceId, $subpath, $createDynamicFolders = true)
	{

		// Get the root folder in the source
		$rootFolder = craft()->assets->getRootFolderBySourceId($sourceId);

		// Make sure the root folder actually exists
		if (!$rootFolder)
		{
			throw new InvalidSourceException();
		}

		// Are we looking for a subfolder?
		$subpath = is_string($subpath) ? trim($subpath, '/') : '';

		if (strlen($subpath) === 0)
		{
			$folder = $rootFolder;
		}
		else
		{
			// Prepare the path by parsing tokens and normalizing slashes.
			try
			{
				$renderedSubpath = craft()->templates->renderObjectTemplate($subpath, $this->element);
			}
			catch (\Exception $e)
			{
				throw new InvalidSubpathException($subpath);
			}

			// Did any of the tokens return null?
			if (
				strlen($renderedSubpath) === 0 ||
				trim($renderedSubpath, '/') != $renderedSubpath ||
				strpos($renderedSubpath, '//') !== false
			)
			{
				throw new InvalidSubpathException($subpath);
			}

			$subpath = IOHelper::cleanPath($renderedSubpath, craft()->config->get('convertFilenamesToAscii'));

			$folder = craft()->assets->findFolder(array(
				'sourceId' => $sourceId,
				'path'     => $subpath.'/'
			));

			// Ensure that the folder exists
			if (!$folder)
			{
				if (!$createDynamicFolders)
				{
					throw new InvalidSubpathException($subpath);
				}

				// Start at the root, and, go over each folder in the path and create it if it's missing.
				$parentFolder = $rootFolder;

				$segments = explode('/', $subpath);

				foreach ($segments as $segment)
				{
					$folder = craft()->assets->findFolder(array(
						'parentId' => $parentFolder->id,
						'name' => $segment
					));

					// Create it if it doesn't exist
					if (!$folder)
					{
						$folderId = $this->_createSubFolder($parentFolder, $segment);
						$folder = craft()->assets->getFolderById($folderId);
					}

					// In case there's another segment after this...
					$parentFolder = $folder;
				}
			}
		}

		return $folder->id;
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
		$response = craft()->assets->createFolder($currentFolder->id, $folderName);

		if ($response->isError() || $response->isConflict())
		{
			// If folder doesn't exist in DB, but we can't create it, it probably exists on the server.
			$newFolder = new AssetFolderModel(
				array(
					'parentId' => $currentFolder->id,
					'name' => $folderName,
					'sourceId' => $currentFolder->sourceId,
					'path' => ($currentFolder->parentId ? $currentFolder->path.$folderName : $folderName).'/'
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
	 *
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
	 * @param BaseModel $settings
	 * @param bool $createDynamicFolders whether missing folders should be created in the process
	 *
	 * @throws Exception
	 * @return mixed|null
	 */
	private function _determineUploadFolderId($settings, $createDynamicFolders = true)
	{
		// Use the appropriate settings for folder determination
		if (empty($settings->useSingleFolder))
		{
			$folderSourceId = $settings->defaultUploadLocationSource;
			$folderSubpath = $settings->defaultUploadLocationSubpath;
		}
		else
		{
			$folderSourceId = $settings->singleUploadLocationSource;
			$folderSubpath = $settings->singleUploadLocationSubpath;
		}

		// Attempt to find the actual folder ID
		try
		{
			$folderId = $this->_resolveSourcePathToFolderId($folderSourceId, $folderSubpath, $createDynamicFolders);
		}
		catch (InvalidSubpathException $e)
		{
			// If this is a new/disabled element, the subpath probably just contained a token that returned null, like {id}
			// so use the user's upload folder instead
			if (!isset($this->element) || !$this->element->id || !$this->element->enabled || !$createDynamicFolders)
			{
				$userModel = craft()->userSession->getUser();
				$userFolder = craft()->assets->getUserFolder($userModel);
				$folderName = 'field_'.$this->model->id;

				$folder = craft()->assets->findFolder(array(
					'parentId' => $userFolder->id,
					'name'     => $folderName
				));

				if ($folder)
				{
					$folderId = $folder->id;
				}
				else
				{
					$folderId = $this->_createSubFolder($userFolder, $folderName);
				}

				IOHelper::ensureFolderExists(craft()->path->getAssetsTempSourcePath().$folderName);
			}
			else
			{
				// Existing element, so this is just a bad subpath
				throw $e;
			}
		}

		return $folderId;
	}
}
