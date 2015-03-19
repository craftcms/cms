<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\elements\db\MatrixBlockQuery;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\StringHelper;
use craft\app\elements\MatrixBlock;
use craft\app\models\MatrixBlockType;

/**
 * Matrix represents a Matrix field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Matrix extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Matrix');
	}

	// Properties
	// =========================================================================

	/**
	 * @var integer Max blocks
	 */
	public $maxBlocks;



	/**
	 * @var MatrixBlockType[] The field’s block types
	 */
	private $_blockTypes;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['maxBlocks'], 'integer', 'min' => 0];
		return $rules;
	}

	/**
	 * Returns the block types.
	 *
	 * @return MatrixBlockType[]
	 */
	public function getBlockTypes()
	{
		if (!isset($this->_blockTypes))
		{
			if (!empty($this->id))
			{
				$this->_blockTypes = Craft::$app->matrix->getBlockTypesByFieldId($this->id);
			}
			else
			{
				$this->_blockTypes = [];
			}
		}

		return $this->_blockTypes;
	}

	/**
	 * Sets the block types.
	 *
	 * @param MatrixBlockType|array $blockTypes The block type settings or actual MatrixBlockType model instances
	 */
	public function setBlockTypes($blockTypes)
	{
		$this->_blockTypes = [];

		foreach ($blockTypes as $key => $config)
		{
			if ($config instanceof MatrixBlockType)
			{
				$this->_blockTypes[] = $config;
			}
			else
			{
				$blockType = new MatrixBlockType();
				$blockType->id = $key;
				$blockType->fieldId = $this->id;
				$blockType->name = $config['name'];
				$blockType->handle = $config['handle'];

				$fields = [];

				if (!empty($config['fields']))
				{
					foreach ($config['fields'] as $fieldId => $fieldConfig)
					{
						$fields[] = Craft::$app->fields->createField([
							'type'         => $fieldConfig['type'],
							'id'           => $fieldId,
							'name'         => $fieldConfig['name'],
							'handle'       => $fieldConfig['handle'],
							'instructions' => $fieldConfig['instructions'],
							'required'     => !empty($fieldConfig['required']),
							'translatable' => !empty($fieldConfig['translatable']),
							'settings'     => (isset($fieldConfig['typesettings']) ? $fieldConfig['typesettings'] : null),
						]);
					}
				}

				$blockType->setFields($fields);
				$this->_blockTypes[] = $blockType;
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validate($attributeNames = null, $clearErrors = true)
	{
		// Run basic model validation first
		$validates = parent::validate($attributeNames, $clearErrors);

		// Run Matrix field validation as well
		if (!Craft::$app->matrix->validateFieldSettings($this))
		{
			$validates = false;
		}

		return $validates;
	}

	/**
	 * @inheritdoc
	 */
	public static function hasContentColumn()
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		// Get the available field types data
		$fieldTypeInfo = $this->_getFieldOptionsForConfigurator();

		Craft::$app->templates->includeJsResource('js/MatrixConfigurator.js');
		Craft::$app->templates->includeJs(
			'new Craft.MatrixConfigurator(' .
			JsonHelper::encode($fieldTypeInfo, JSON_UNESCAPED_UNICODE).', ' .
			JsonHelper::encode(Craft::$app->templates->getNamespace(), JSON_UNESCAPED_UNICODE) .
			');'
		);

		Craft::$app->templates->includeTranslations(
			'What this block type will be called in the CP.',
			'How you’ll refer to this block type in the templates.',
			'Are you sure you want to delete this block type?',
			'This field is required',
			'This field is translatable',
			'Field Type',
			'Are you sure you want to delete this field?'
		);

		$fieldTypeOptions = [];

		foreach (Craft::$app->fields->getAllFieldTypes() as $class)
		{
			// No Matrix-Inception, sorry buddy.
			if ($class !== self::className())
			{
				$fieldTypeOptions[] = ['value' => $class, 'label' => $class::displayName()];
			}
		}

		return Craft::$app->templates->render('_components/fieldtypes/Matrix/settings', [
			'matrixField' => $this,
			'fieldTypes'  => $fieldTypeOptions
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave()
	{
		Craft::$app->matrix->saveSettings($this, false);
	}

	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		Craft::$app->matrix->deleteMatrixField($this);
	}

	/**
	 * @inheritdoc
	 */
	public function prepValue($value)
	{
		$query = MatrixBlock::find();

		// Existing element?
		if (!empty($this->element->id))
		{
			$query->ownerId($this->element->id);
		}
		else
		{
			$query->id(false);
		}

		$query
			->fieldId($this->id)
			->locale($this->element->locale);

		// Set the initially matched elements if $value is already set, which is the case if there was a validation
		// error or we're loading an entry revision.
		if (is_array($value) || $value === '')
		{
			$query
				->status(null)
				->localeEnabled(false)
				->limit(null);

			if (is_array($value))
			{
				$prevElement = null;

				foreach ($value as $element)
				{
					if ($prevElement)
					{
						$prevElement->setNext($element);
						$element->setPrev($prevElement);
					}

					$prevElement = $element;
				}

				$query->setResult($value);
			}
			else if ($value === '')
			{
				// Means there were no blocks
				$query->setResult([]);
			}
		}

		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($name, $value)
	{
		$id = Craft::$app->templates->formatInputId($name);

		// Get the block types data
		$blockTypeInfo = $this->_getBlockTypeInfoForInput($name);

		Craft::$app->templates->includeJsResource('js/MatrixInput.js');

		Craft::$app->templates->includeJs('new Craft.MatrixInput(' .
			'"'.Craft::$app->templates->namespaceInputId($id).'", ' .
			JsonHelper::encode($blockTypeInfo, JSON_UNESCAPED_UNICODE).', ' .
			'"'.Craft::$app->templates->namespaceInputName($name).'", ' .
			($this->maxBlocks ? $this->maxBlocks : 'null') .
		');');

		Craft::$app->templates->includeTranslations('Disabled', 'Actions', 'Collapse', 'Expand', 'Disable', 'Enable', 'Add {type} above', 'Add a block');

		if ($value instanceof MatrixBlockQuery)
		{
			$value
				->limit(null)
				->status(null)
				->localeEnabled(false);
		}

		return Craft::$app->templates->render('_components/fieldtypes/Matrix/input', [
			'id' => $id,
			'name' => $name,
			'blockTypes' => $this->getBlockTypes(),
			'blocks' => $value,
			'static' => false
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function prepValueFromPost($data)
	{
		// Get the possible block types for this field
		$blockTypes = Craft::$app->matrix->getBlockTypesByFieldId($this->id, 'handle');

		if (!is_array($data))
		{
			return [];
		}

		$oldBlocksById = [];

		// Get the old blocks that are still around
		if (!empty($this->element->id))
		{
			$ownerId = $this->element->id;

			$ids = [];

			foreach (array_keys($data) as $blockId)
			{
				if (is_numeric($blockId) && $blockId != 0)
				{
					$ids[] = $blockId;
				}
			}

			if ($ids)
			{
				$oldBlocksById = MatrixBlock::find()
					->fieldId($this->id)
					->ownerId($ownerId)
					->id($ids)
					->limit(null)
					->status(null)
					->localeEnabled(false)
					->locale($this->element->locale)
					->indexBy('id')
					->all();
			}
		}
		else
		{
			$ownerId = null;
		}

		$blocks = [];
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
				$block = new MatrixBlock();
				$block->fieldId = $this->id;
				$block->typeId  = $blockType->id;
				$block->ownerId = $ownerId;
				$block->locale  = $this->element->locale;

				// Preserve the collapsed state, which the browser can't remember on its own for new blocks
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
				$block->setContentPostLocation("{$ownerContentPostLocation}.{$this->handle}.{$blockId}.fields");
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
	 * @inheritdoc
	 */
	public function validateValue($blocks)
	{
		$errors = [];
		$blocksValidate = true;

		foreach ($blocks as $block)
		{
			if (!Craft::$app->matrix->validateBlock($block))
			{
				$blocksValidate = false;
			}
		}

		if (!$blocksValidate)
		{
			$errors[] = Craft::t('app', 'Correct the errors listed above.');
		}

		if ($this->maxBlocks && count($blocks) > $this->maxBlocks)
		{
			if ($this->maxBlocks == 1)
			{
				$errors[] = Craft::t('app', 'There can’t be more than one block.');
			}
			else
			{
				$errors[] = Craft::t('app', 'There can’t be more than {max} blocks.', ['max' => $this->maxBlocks]);
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
	 * @inheritdoc
	 *
	 * @param MatrixBlockQuery $value
	 * @return string
	 */
	public function getSearchKeywords($value)
	{
		$keywords = [];
		$contentService = Craft::$app->content;

		foreach ($value as $block)
		{
			$originalContentTable      = $contentService->contentTable;
			$originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
			$originalFieldContext      = $contentService->fieldContext;

			$contentService->contentTable      = $block->getContentTable();
			$contentService->fieldColumnPrefix = $block->getFieldColumnPrefix();
			$contentService->fieldContext      = $block->getFieldContext();

			foreach (Craft::$app->fields->getAllFields() as $field)
			{
				$field->element = $block;
				$handle = $field->handle;
				$keywords[] = $field->getSearchKeywords($block->getFieldValue($handle));
			}

			$contentService->contentTable      = $originalContentTable;
			$contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
			$contentService->fieldContext      = $originalFieldContext;
		}

		return parent::getSearchKeywords($keywords);
	}

	/**
	 * @inheritdoc
	 */
	public function afterElementSave()
	{
		Craft::$app->matrix->saveField($this);
	}

	/**
	 * @inheritdoc
	 */
	public function getStaticHtml($value)
	{
		if ($value)
		{
			$id = StringHelper::randomString();

			return Craft::$app->templates->render('_components/fieldtypes/Matrix/input', [
				'id' => $id,
				'name' => $id,
				'blockTypes' => $this->getBlockTypes(),
				'blocks' => $value,
				'static' => true
			]);
		}
		else
		{
			return '<p class="light">'.Craft::t('app', 'No blocks.').'</p>';
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns info about each field type for the configurator.
	 *
	 * @return array
	 */
	private function _getFieldOptionsForConfigurator()
	{
		$fieldTypes = [];

		// Set a temporary namespace for these
		$originalNamespace = Craft::$app->templates->getNamespace();
		$namespace = Craft::$app->templates->namespaceInputName('blockTypes[__BLOCK_TYPE__][fields][__FIELD__][typesettings]', $originalNamespace);
		Craft::$app->templates->setNamespace($namespace);

		foreach (Craft::$app->fields->getAllFieldTypes() as $class)
		{
			// No Matrix-Inception, sorry buddy.
			if ($class === self::className())
			{
				continue;
			}

			Craft::$app->templates->startJsBuffer();
			/** @var FieldInterface $field */
			$field = new $class();
			$settingsBodyHtml = Craft::$app->templates->namespaceInputs($field->getSettingsHtml());
			$settingsFootHtml = Craft::$app->templates->clearJsBuffer();

			$fieldTypes[] = [
				'type'             => $class,
				'name'             => $class::displayName(),
				'settingsBodyHtml' => $settingsBodyHtml,
				'settingsFootHtml' => $settingsFootHtml,
			];
		}

		Craft::$app->templates->setNamespace($originalNamespace);

		return $fieldTypes;
	}

	/**
	 * Returns info about each field type for the configurator.
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	private function _getBlockTypeInfoForInput($name)
	{
		$blockTypes = [];

		// Set a temporary namespace for these
		$originalNamespace = Craft::$app->templates->getNamespace();
		$namespace = Craft::$app->templates->namespaceInputName($name.'[__BLOCK__][fields]', $originalNamespace);
		Craft::$app->templates->setNamespace($namespace);

		foreach ($this->getBlockTypes() as $blockType)
		{
			// Create a fake MatrixBlock so the field types have a way to get at the owner element, if there is one
			$block = new MatrixBlock();
			$block->fieldId = $this->id;
			$block->typeId = $blockType->id;

			if ($this->element)
			{
				$block->setOwner($this->element);
			}

			$fieldLayoutFields = $blockType->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $field)
			{
				$field->element = $block;
				$field->setIsFresh(true);
			}

			Craft::$app->templates->startJsBuffer();

			$bodyHtml = Craft::$app->templates->namespaceInputs(Craft::$app->templates->render('_includes/fields', [
				'namespace' => null,
				'fields'    => $fieldLayoutFields
			]));

			// Reset $_isFresh's
			foreach ($fieldLayoutFields as $field)
			{
				$field->setIsFresh(null);
			}

			$footHtml = Craft::$app->templates->clearJsBuffer();

			$blockTypes[] = [
				'handle'   => $blockType->handle,
				'name'     => Craft::t('app', $blockType->name),
				'bodyHtml' => $bodyHtml,
				'footHtml' => $footHtml,
			];
		}

		Craft::$app->templates->setNamespace($originalNamespace);

		return $blockTypes;
	}
}
