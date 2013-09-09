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
	 * @param mixed  $elements
	 * @return string
	 */
	public function getInputHtml($name, $elements)
	{
		$id = rtrim(preg_replace('/[\[\]]+/', '-', $name), '-').'-'.StringHelper::UUID();

		if (!($elements instanceof RelationFieldData))
		{
			$elements = new RelationFieldData();
		}

		$criteria = array('status' => null);

		if (!empty($this->element->id))
		{
			$criteria['id'] = 'not '.$this->element->id;
		}

		$settings = $this->getSettings();
		if ($this->allowMultipleSources)
		{
			$sources = $settings->sources;
		}
		else
		{
			$sources = array($settings->source);
		}

		// Look for the sourcePath
		if (isset($settings->sourcePath) && !empty($settings->sourcePath))
		{
			// It must sturt with a folder or a source.
			$sourcePath = $settings->sourcePath;
			if (preg_match('/^\{((folder|source):[0-9]+)\}/', $sourcePath, $matches))
			{
				// Is this a saved entry and can the path be resolved then?
				if ($this->element->id)
				{
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
						$sources = array('*');
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
						$criteria = array('sourceId' => $sourceId, 'fullPath' => $folder->fullPath . $sourcePath);
						$existingFolder = craft()->assets->findFolder($criteria);

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
								$criteria = array('parentId' => $currentFolder->id, 'name' => $part);
								$existingFolder = craft()->assets->findFolder($criteria);
								if (!$existingFolder)
								{
									$response = craft()->assets->createFolder($currentFolder->id, $part);
									if ($response->isError() || $response->isConflict())
									{
										// 99% of the time this will happen because a folder exists on the source, so let's just insert it into DB.
										$newFolder = new AssetFolderModel(
											array(
												'parentId' => $currentFolder->id,
												'name'     => $part,
												'sourceId' => $sourceId,
												'fullPath' => ltrim($currentFolder->fullPath.'/'.$part, '/')
											)
										);
										$folderId = craft()->assets->storeFolder($newFolder);
									}
									else
									{
										$folderId = $response->getDataItem('folderId');
									}
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
					$sourcePath = 'path:'.$currentFolder->id;
				}
				else
				{
					// Hash the path for new entries. This ensure that all unsaved entries
					// with fields with identical sourcepath settings will resolve to the same temp folder.
					$sourcePath = 'path:draft:'.sha1($sourcePath);
				}
			}
		}
		else
		{
			$sourcePath = '';
		}

		return craft()->templates->render('_includes/forms/elementSelect', array(
			'jsClass'        => $this->inputJsClass,
			'elementType'    => new ElementTypeVariable($this->getElementType()),
			'id'             => $id,
			'name'           => $name,
			'elements'       => $elements->all,
			'sources'        => $sources,
			'sourcePath'     => $sourcePath,
			'criteria'       => $criteria,
			'limit'          => ($this->allowLimit ? $this->getSettings()->limit : null),
			'addButtonLabel' => $this->getAddButtonLabel(),
		));
	}
}
