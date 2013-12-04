<?php
namespace Craft;

/**
 * Assets fieldtype
 */
class AssetsFieldType extends BaseElementFieldType
{
	/**
	 * @access protected
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Asset';

	/**
	 * @access protected
	 * @var string|null $inputJsClass The JS class that should be initialized for the input.
	 */
	protected $inputJsClass = 'Craft.AssetSelectInput';

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add an asset');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		$settings = parent::defineSettings();
		$settings['sourcePath'] = AttributeType::String;

		return $settings;
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		if (!(isset($settings['useSourcePath']) && $settings['useSourcePath']))
		{
			$settings['sourcePath'] = '';
		}
		return $settings;
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$sources = array();

		foreach ($this->getElementType()->getSources() as $key => $source)
		{
			if (!isset($source['heading']))
			{
				$sources[] = array('label' => $source['label'], 'value' => $key);
			}
		}

		return craft()->templates->render('_components/fieldtypes/Assets/settings', array(
			'allowMultipleSources' => $this->allowMultipleSources,
			'allowLimit'           => $this->allowLimit,
			'sources'              => $sources,
			'settings'             => $this->getSettings(),
			'type'                 => $this->getName()
		));
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 * @return string
	 * @throws Exception
	 */
	public function getInputHtml($name, $criteria)
	{
		// Look for the single folder setting
		$settings = $this->getSettings();
		if (isset($settings->sourcePath) && !empty($settings->sourcePath))
		{
			// It must start with a folder or a source.
			$sourcePath = $settings->sourcePath;
			if (preg_match('/^\{((folder|source):[0-9]+)\}/', $sourcePath, $matches))
			{
				// Is this a saved entry and can the path be resolved then?
				if ($this->element->id)
				{
					$sourcePath = 'folder:'.$this->_resolveSourcePathToFolderId($sourcePath);
				}
				else
				{
					// New entry, so we default to User's upload folder for this field
					$userModel = craft()->userSession->getUser();
					if (!$userModel)
					{
						throw new Exception(Craft::t("To use this Field, user must be logged in!"));
					}

					$userFolder = craft()->assets->getUserFolder($userModel);

					$folderName = 'field_' . $this->model->id;
					$elementFolder = craft()->assets->findFolder(array('parentId' => $userFolder->id, 'name' => $folderName));
					if (!($elementFolder))
					{
						$folderId = $this->_createSubFolder($userFolder, $folderName);
					}
					else
					{
						$folderId = $elementFolder->id;
					}
					$sourcePath = 'folder:'.$folderId;
				}
			}
		}
		else
		{
			$sourcePath = null;
		}

		$variables = array();

		// If we have a source path, override the source variable
		if ($sourcePath)
		{
			$variables['sources'] = $sourcePath;
		}

		return parent::getInputHtml($name, $criteria, $variables);
	}

	/**
	 * Resolve a source path to it's folder ID by the source path and the matched source beginning.
	 *
	 * @param $sourcePath
	 * @return mixed
	 * @throws Exception
	 */
	private function _resolveSourcePathToFolderId($sourcePath)
	{
		preg_match('/^\{((folder|source):[0-9]+)\}/', $sourcePath, $matches);
		$parts = explode(":", $matches[1]);
		if ($parts[0] == 'folder')
		{
			$folder = craft()->assets->getFolderById($parts[1]);
		}
		else
		{
			$folder = craft()->assets->findFolder(array('sourceId' => $parts[1], 'parentId' => FolderCriteriaModel::AssetsNoParent));
		}

		// Do we have the folder?
		if (empty($folder))
		{
			throw new Exception (Craft::t("Cannot find the target folder."));
		}
		else
		{
			$sourceId = $folder->sourceId;

			// Prepare the path by parsing tokens and normalizing slashes.
			$sourcePath = trim(str_replace('{'.$matches[1].'}', '', $sourcePath), '/');
			$sourcePath = craft()->templates->renderObjectTemplate($sourcePath, $this->element);
			if (strlen($sourcePath))
			{
				$sourcePath = $sourcePath.'/';
			}

			// Let's see if the folder already exists.
			$folderCriteria = array('sourceId' => $sourceId, 'fullPath' => $folder->fullPath . $sourcePath);
			$existingFolder = craft()->assets->findFolder($folderCriteria);

			// No dice, go over each folder in the path and create it if it's missing.
			if (!$existingFolder)
			{
				$parts = explode('/', $sourcePath);

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
					'fullPath' => trim($currentFolder->fullPath . '/' . $folderName, '/') . '/'
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
	 * For all new entries, if the field is using a single folder setting, move the uploaded files.
	 */
	public function onAfterElementSave()
	{
		$settings = $this->getSettings();
		if (isset($settings->sourcePath) && !empty($settings->sourcePath))
		{
			$handle = $this->model->handle;
			$filesToMove = $this->element->getContent()->{$handle};
			if (count($filesToMove))
			{
				$targetFolderId = $this->_resolveSourcePathToFolderId($settings->sourcePath);

				// Resolve conflicts by keeping both
				$actions = array_fill(0, count($filesToMove), AssetsHelper::ActionKeepBoth);
				craft()->assets->moveFiles($filesToMove, $targetFolderId, '', $actions);

			}
		}
		parent::onAfterElementSave();
	}
}
