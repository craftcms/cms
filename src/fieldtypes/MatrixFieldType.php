<?php
namespace Craft;

/**
 * Class MatrixFieldType
 *
 * @package craft.app.fieldtypes
 */
class MatrixFieldType extends BaseFieldType
{
	private $_postedSettings;

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Matrix');
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return false;
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// Get the available field types data
		$fieldTypeInfo = $this->_getFieldTypeInfoForConfigurator();

		craft()->templates->includeJsResource('js/MatrixConfigurator.js');
		craft()->templates->includeJs('new Craft.MatrixConfigurator('.JsonHelper::encode($fieldTypeInfo).', "'.craft()->templates->getNamespace().'");');

		craft()->templates->includeTranslations(
			'What this block type will be called in the CP.',
			'How you’ll refer to this block type in the templates.',
			'Are you sure you want to delete this block type?',
			'This field is required',
			'This field is translatable',
			'Field Type',
			'Are you sure you want to delete this field?'
		);

		$fieldTypeOptions = array();

		foreach (craft()->fields->getAllFieldTypes() as $fieldType)
		{
			// No Matrix-Inception, sorry buddy.
			if ($fieldType->getClassHandle() != 'Matrix')
			{
				$fieldTypeOptions[] = array('label' => $fieldType->getName(), 'value' => $fieldType->getClassHandle());
			}
		}

		return craft()->templates->render('_components/fieldtypes/Matrix/settings', array(
			'settings'   => $this->getSettings(),
			'fieldTypes' => $fieldTypeOptions
		));
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		if ($settings instanceof MatrixSettingsModel)
		{
			return $settings;
		}

		$matrixSettings = new MatrixSettingsModel($this->model);
		$blockTypes = array();

		if (!empty($settings['blockTypes']))
		{
			foreach ($settings['blockTypes'] as $blockTypeId => $blockTypeSettings)
			{
				$blockType = new MatrixBlockTypeModel();
				$blockType->id     = $blockTypeId;
				$blockType->name   = $blockTypeSettings['name'];
				$blockType->handle = $blockTypeSettings['handle'];

				$fields = array();

				if (!empty($blockTypeSettings['fields']))
				{
					foreach ($blockTypeSettings['fields'] as $fieldId => $fieldSettings)
					{
						$field = new FieldModel();
						$field->id           = $fieldId;
						$field->name         = $fieldSettings['name'];
						$field->handle       = $fieldSettings['handle'];
						$field->required     = !empty($fieldSettings['required']);
						$field->translatable = !empty($fieldSettings['translatable']);
						$field->type         = $fieldSettings['type'];

						if (isset($fieldSettings['typesettings']))
						{
							$field->settings = $fieldSettings['typesettings'];
						}

						$fields[] = $field;
					}
				}

				$blockType->setFields($fields);
				$blockTypes[] = $blockType;
			}
		}

		$matrixSettings->setBlockTypes($blockTypes);

		if (!empty($settings['maxBlocks']))
		{
			$matrixSettings->maxBlocks = $settings['maxBlocks'];
		}

		return $matrixSettings;
	}

	/**
	 * Performs any actions after a field is saved.
	 */
	public function onAfterSave()
	{
		craft()->matrix->saveSettings($this->getSettings(), false);
	}

	/**
	 * Performs any actions before a field is deleted.
	 */
	public function onBeforeDelete()
	{
		craft()->matrix->deleteMatrixField($this->model);
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return ElementCriteriaModel|array
	 */
	public function prepValue($value)
	{
		// $value will be an array of block data or an empty string if there was a validation error
		// or we're loading a draft/version.
		if (is_array($value))
		{
			return $value;
		}
		else if ($value === '')
		{
			return array();
		}
		else
		{
			$criteria = craft()->elements->getCriteria(ElementType::MatrixBlock);

			// Existing element?
			if (!empty($this->element->id))
			{
				$criteria->ownerId = $this->element->id;
			}
			else
			{
				$criteria->id = false;
			}

			$criteria->fieldId = $this->model->id;
			$criteria->locale = $this->element->locale;

			return $criteria;
		}
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$id = craft()->templates->formatInputId($name);
		$settings = $this->getSettings();

		// Get the block types data
		$blockTypeInfo = $this->_getBlockTypeInfoForInput($name);

		craft()->templates->includeJsResource('js/MatrixInput.js');
		craft()->templates->includeJs('new Craft.MatrixInput(' .
			'"'.craft()->templates->namespaceInputId($id).'", ' .
			JsonHelper::encode($blockTypeInfo).', ' .
			'"'.craft()->templates->namespaceInputName($name).'", ' .
			($settings->maxBlocks ? $settings->maxBlocks : 'null') .
		');');

		craft()->templates->includeTranslations('Disabled', 'Actions', 'Collapse', 'Expand', 'Disable', 'Enable', 'Add {type} above', 'Add a block');

		if ($value instanceof ElementCriteriaModel)
		{
			$value->limit = null;
			$value->status = null;
			$value->localeEnabled = null;
		}

		return craft()->templates->render('_components/fieldtypes/Matrix/input', array(
			'id' => $id,
			'name' => $name,
			'blockTypes' => $settings->getBlockTypes(),
			'blocks' => $value,
			'static' => false
		));
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function prepValueFromPost($data)
	{
		// Get the possible block types for this field
		$blockTypes = craft()->matrix->getBlockTypesByFieldId($this->model->id, 'handle');

		if (!is_array($data))
		{
			return array();
		}

		$oldBlocksById = array();

		// Get the old blocks that are still around
		if (!empty($this->element->id))
		{
			$ownerId = $this->element->id;

			$ids = array();

			foreach (array_keys($data) as $blockId)
			{
				if (is_numeric($blockId) && $blockId != 0)
				{
					$ids[] = $blockId;
				}
			}

			if ($ids)
			{
				$criteria = craft()->elements->getCriteria(ElementType::MatrixBlock);
				$criteria->fieldId = $this->model->id;
				$criteria->ownerId = $ownerId;
				$criteria->id = $ids;
				$criteria->limit = null;
				$criteria->status = null;
				$criteria->localeEnabled = null;
				$criteria->locale = $this->element->locale;
				$oldBlocks = $criteria->find();

				// Index them by ID
				foreach ($oldBlocks as $oldBlock)
				{
					$oldBlocksById[$oldBlock->id] = $oldBlock;
				}
			}
		}
		else
		{
			$ownerId = null;
		}

		$blocks = array();
		$sortOrder = 0;

		foreach ($data as $blockId => $blockData)
		{
			if (!isset($blockData['type']) || !isset($blockTypes[$blockData['type']]))
			{
				continue;
			}

			$blockType = $blockTypes[$blockData['type']];

			// Is this new? (Or has it been deleted?)
			if (strncmp($blockId, 'new', 3) === 0 || !isset($oldBlocksById[$blockId]))
			{
				$block = new MatrixBlockModel();
				$block->fieldId = $this->model->id;
				$block->typeId  = $blockType->id;
				$block->ownerId = $ownerId;
				$block->locale  = $this->element->locale;

				// Preserve the collapsed state, which the browser can't remember on its own for neww blocks
				$block->collapsed = !empty($blockData['collapsed']);
			}
			else
			{
				$block = $oldBlocksById[$blockId];
			}

			$block->setOwner($this->element);
			$block->enabled = (isset($blockData['enabled']) ? (bool) $blockData['enabled'] : true);

			// Set the content post location on the block if we can
			$ownerContentPostLocation = $this->element->getContentPostLocation();

			if ($ownerContentPostLocation)
			{
				$block->setContentPostLocation("{$ownerContentPostLocation}.{$this->model->handle}.{$blockId}.fields");
			}

			if (isset($blockData['fields']))
			{
				$block->setContentFromPost($blockData['fields']);
			}

			$sortOrder++;
			$block->sortOrder = $sortOrder;

			$blocks[] = $block;
		}

		return $blocks;
	}

	/**
	 * Validates the value beyond the checks that were assumed based on the content attribute.
	 *
	 * Returns 'true' or any custom validation errors.
	 *
	 * @param array $blocks
	 * @return true|string|array
	 */
	public function validate($blocks)
	{
		$errors = array();
		$blocksValidate = true;

		foreach ($blocks as $block)
		{
			if (!craft()->matrix->validateBlock($block))
			{
				$blocksValidate = false;
			}
		}

		if (!$blocksValidate)
		{
			$errors[] = Craft::t('Correct the errors listed above.');
		}

		$maxBlocks = $this->getSettings()->maxBlocks;

		if ($maxBlocks && count($blocks) > $maxBlocks)
		{
			if ($maxBlocks == 1)
			{
				$errors[] = Craft::t('There can’t be more than one block.');
			}
			else
			{
				$errors[] = Craft::t('There can’t be more than {max} blocks.', array('max' => $maxBlocks));
			}
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
	 * Returns the search keywords that should be associated with this field,
	 * based on the prepped post data.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function getSearchKeywords($value)
	{
		$criteria = $this->prepValue(null);
		$keywords = array();
		$contentService = craft()->content;

		foreach ($criteria->find() as $block)
		{
			$originalContentTable      = $contentService->contentTable;
			$originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
			$originalFieldContext      = $contentService->fieldContext;

			$contentService->contentTable      = $block->getContentTable();
			$contentService->fieldColumnPrefix = $block->getFieldColumnPrefix();
			$contentService->fieldContext      = $block->getFieldContext();

			foreach (craft()->fields->getAllFields() as $field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType)
				{
					$fieldType->element = $block;
					$handle = $field->handle;
					$keywords[] = $fieldType->getSearchKeywords($block->$handle);
				}
			}

			$contentService->contentTable      = $originalContentTable;
			$contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
			$contentService->fieldContext      = $originalFieldContext;
		}

		return parent::getSearchKeywords($keywords);
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
		craft()->matrix->saveField($this);
	}

	/**
	 * Returns static HTML for the field's value.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		if ($value)
		{
			$settings = $this->getSettings();
			$id = StringHelper::randomString();

			return craft()->templates->render('_components/fieldtypes/Matrix/input', array(
				'id' => $id,
				'name' => $id,
				'blockTypes' => $settings->getBlockTypes(),
				'blocks' => $value,
				'static' => true
			));
		}
		else
		{
			return '<p class="light">'.Craft::t('No blocks.').'</p>';
		}
	}

	/**
	 * Returns the settings model.
	 *
	 * @return BaseModel
	 */
	protected function getSettingsModel()
	{
		return new MatrixSettingsModel($this->model);
	}

	/**
	 * Returns info about each field type for the configurator.
	 *
	 * @return array
	 */
	private function _getFieldTypeInfoForConfigurator()
	{
		$fieldTypes = array();

		$originalNamespace = craft()->templates->getNamespace();
		$namespace = craft()->templates->namespaceInputName('blockTypes[__BLOCK_TYPE__][fields][__FIELD__][typesettings]', $originalNamespace);
		craft()->templates->setNamespace($namespace);

		foreach (craft()->fields->getAllFieldTypes() as $fieldType)
		{
			$fieldTypeClass = $fieldType->getClassHandle();

			// No Matrix-Inception, sorry buddy.
			if ($fieldTypeClass == 'Matrix')
			{
				continue;
			}

			craft()->templates->startJsBuffer();
			$settingsBodyHtml = craft()->templates->namespaceInputs($fieldType->getSettingsHtml());
			$settingsFootHtml = craft()->templates->clearJsBuffer();

			$fieldTypes[] = array(
				'type'             => $fieldTypeClass,
				'name'             => $fieldType->getName(),
				'settingsBodyHtml' => $settingsBodyHtml,
				'settingsFootHtml' => $settingsFootHtml,
			);
		}

		craft()->templates->setNamespace($originalNamespace);

		return $fieldTypes;
	}

	/**
	 * Returns info about each field type for the configurator.
	 *
	 * @param string $name
	 * @return array
	 */
	private function _getBlockTypeInfoForInput($name)
	{
		$blockTypes = array();

		$originalNamespace = craft()->templates->getNamespace();
		$namespace = craft()->templates->namespaceInputName($name.'[__BLOCK__][fields]', $originalNamespace);
		craft()->templates->setNamespace($namespace);

		foreach ($this->getSettings()->getBlockTypes() as $blockType)
		{
			craft()->templates->startJsBuffer();

			$bodyHtml = craft()->templates->namespaceInputs(craft()->templates->render('_includes/fields', array(
				'namespace' => null,
				'fields' => $blockType->getFieldLayout()->getFields()
			)));

			$footHtml = craft()->templates->clearJsBuffer();

			$blockTypes[] = array(
				'handle'   => $blockType->handle,
				'name'     => Craft::t($blockType->name),
				'bodyHtml' => $bodyHtml,
				'footHtml' => $footHtml,
			);
		}

		craft()->templates->setNamespace($originalNamespace);

		return $blockTypes;
	}
}
