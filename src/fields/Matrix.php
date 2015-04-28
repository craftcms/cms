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
				$this->_blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id);
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
						$fields[] = Craft::$app->getFields()->createField([
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
		if (!Craft::$app->getMatrix()->validateFieldSettings($this))
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

		Craft::$app->getView()->registerJsResource('js/MatrixConfigurator.js');
		Craft::$app->getView()->registerJs(
			'new Craft.MatrixConfigurator(' .
			JsonHelper::encode($fieldTypeInfo, JSON_UNESCAPED_UNICODE).', ' .
			JsonHelper::encode(Craft::$app->getView()->getNamespace(), JSON_UNESCAPED_UNICODE) .
			');'
		);

		Craft::$app->getView()->includeTranslations(
			'What this block type will be called in the CP.',
			'How you’ll refer to this block type in the templates.',
			'Are you sure you want to delete this block type?',
			'This field is required',
			'This field is translatable',
			'Field Type',
			'Are you sure you want to delete this field?'
		);

		$fieldTypeOptions = [];

		foreach (Craft::$app->getFields()->getAllFieldTypes() as $class)
		{
			// No Matrix-Inception, sorry buddy.
			if ($class !== self::className())
			{
				$fieldTypeOptions[] = ['value' => $class, 'label' => $class::displayName()];
			}
		}

		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/settings', [
			'matrixField' => $this,
			'fieldTypes'  => $fieldTypeOptions
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave()
	{
		Craft::$app->getMatrix()->saveSettings($this, false);
		parent::afterSave();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		Craft::$app->getMatrix()->deleteMatrixField($this);
		return parent::beforeDelete();
	}

	/**
	 * @inheritdoc
	 */
	public function prepareValue($value, $element)
	{
		$query = MatrixBlock::find();

		// Existing element?
		if (!empty($element->id))
		{
			$query->ownerId($element->id);
		}
		else
		{
			$query->id(false);
		}

		$query
			->fieldId($this->id)
			->locale($element->locale);

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
						/** @var ElementInterface $prevElement */
						$prevElement->setNext($element);
						/** @var ElementInterface $element */
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
	public function getInputHtml($value, $element)
	{
		$id = Craft::$app->getView()->formatInputId($this->handle);

		// Get the block types data
		$blockTypeInfo = $this->_getBlockTypeInfoForInput($element);

		Craft::$app->getView()->registerJsResource('js/MatrixInput.js');

		Craft::$app->getView()->registerJs('new Craft.MatrixInput(' .
			'"'.Craft::$app->getView()->namespaceInputId($id).'", ' .
			JsonHelper::encode($blockTypeInfo, JSON_UNESCAPED_UNICODE).', ' .
			'"'.Craft::$app->getView()->namespaceInputName($this->handle).'", ' .
			($this->maxBlocks ? $this->maxBlocks : 'null') .
		');');

		Craft::$app->getView()->includeTranslations('Disabled', 'Actions', 'Collapse', 'Expand', 'Disable', 'Enable', 'Add {type} above', 'Add a block');

		if ($value instanceof MatrixBlockQuery)
		{
			$value
				->limit(null)
				->status(null)
				->localeEnabled(false);
		}

		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input', [
			'id' => $id,
			'name' => $this->handle,
			'blockTypes' => $this->getBlockTypes(),
			'blocks' => $value,
			'static' => false
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function validateValue($value, $element)
	{
		$errors = [];
		$blocksValidate = true;

		foreach ($value as $block)
		{
			if (!Craft::$app->getMatrix()->validateBlock($block))
			{
				$blocksValidate = false;
			}
		}

		if (!$blocksValidate)
		{
			$errors[] = Craft::t('app', 'Correct the errors listed above.');
		}

		if ($this->maxBlocks && count($value) > $this->maxBlocks)
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
	public function getSearchKeywords($value, $element)
	{
		$keywords = [];
		$contentService = Craft::$app->getContent();

		foreach ($value as $block)
		{
			$originalContentTable      = $contentService->contentTable;
			$originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
			$originalFieldContext      = $contentService->fieldContext;

			$contentService->contentTable      = $block->getContentTable();
			$contentService->fieldColumnPrefix = $block->getFieldColumnPrefix();
			$contentService->fieldContext      = $block->getFieldContext();

			foreach (Craft::$app->getFields()->getAllFields() as $field)
			{
				$fieldValue = $block->getFieldValue($field->handle);
				$keywords[] = $field->getSearchKeywords($fieldValue, $element);
			}

			$contentService->contentTable      = $originalContentTable;
			$contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
			$contentService->fieldContext      = $originalFieldContext;
		}

		return parent::getSearchKeywords($keywords, $element);
	}

	/**
	 * @inheritdoc
	 */
	public function afterElementSave(ElementInterface $element)
	{
		Craft::$app->getMatrix()->saveField($this, $element);
	}

	/**
	 * @inheritdoc
	 */
	public function getStaticHtml($value, $element)
	{
		if ($value)
		{
			$id = StringHelper::randomString();

			return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input', [
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function prepareValueBeforeSave($value, $element)
	{
		// Get the possible block types for this field
		$blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id, 'handle');

		if (!is_array($value))
		{
			return [];
		}

		$oldBlocksById = [];

		// Get the old blocks that are still around
		if (!empty($element->id))
		{
			$ownerId = $element->id;

			$ids = [];

			foreach (array_keys($value) as $blockId)
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
					->locale($element->locale)
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

		foreach ($value as $blockId => $blockData)
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
				$block->locale  = $element->locale;

				// Preserve the collapsed state, which the browser can't remember on its own for new blocks
				$block->collapsed = !empty($blockData['collapsed']);
			}
			else
			{
				$block = $oldBlocksById[$blockId];
			}

			$block->setOwner($element);
			$block->enabled = (isset($blockData['enabled']) ? (bool) $blockData['enabled'] : true);

			// Set the content post location on the block if we can
			$ownerContentPostLocation = $element->getContentPostLocation();

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
		$originalNamespace = Craft::$app->getView()->getNamespace();
		$namespace = Craft::$app->getView()->namespaceInputName('blockTypes[__BLOCK_TYPE__][fields][__FIELD__][typesettings]', $originalNamespace);
		Craft::$app->getView()->setNamespace($namespace);

		foreach (Craft::$app->getFields()->getAllFieldTypes() as $class)
		{
			// No Matrix-Inception, sorry buddy.
			if ($class === self::className())
			{
				continue;
			}

			Craft::$app->getView()->startJsBuffer();
			/** @var FieldInterface $field */
			$field = new $class();
			$settingsBodyHtml = Craft::$app->getView()->namespaceInputs($field->getSettingsHtml());
			$settingsFootHtml = Craft::$app->getView()->clearJsBuffer();

			$fieldTypes[] = [
				'type'             => $class,
				'name'             => $class::displayName(),
				'settingsBodyHtml' => $settingsBodyHtml,
				'settingsFootHtml' => $settingsFootHtml,
			];
		}

		Craft::$app->getView()->setNamespace($originalNamespace);

		return $fieldTypes;
	}

	/**
	 * Returns info about each field type for the configurator.
	 *
	 * @param ElementInterface|Element $element
	 *
	 * @return array
	 */
	private function _getBlockTypeInfoForInput($element)
	{
		$blockTypes = [];

		// Set a temporary namespace for these
		$originalNamespace = Craft::$app->getView()->getNamespace();
		$namespace = Craft::$app->getView()->namespaceInputName($this->handle.'[__BLOCK__][fields]', $originalNamespace);
		Craft::$app->getView()->setNamespace($namespace);

		foreach ($this->getBlockTypes() as $blockType)
		{
			// Create a fake MatrixBlock so the field types have a way to get at the owner element, if there is one
			$block = new MatrixBlock();
			$block->fieldId = $this->id;
			$block->typeId = $blockType->id;

			if ($element)
			{
				$block->setOwner($element);
			}

			$fieldLayoutFields = $blockType->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $field)
			{
				$field->setIsFresh(true);
			}

			Craft::$app->getView()->startJsBuffer();

			$bodyHtml = Craft::$app->getView()->namespaceInputs(Craft::$app->getView()->renderTemplate('_includes/fields', [
				'namespace' => null,
				'fields'    => $fieldLayoutFields
			]));

			// Reset $_isFresh's
			foreach ($fieldLayoutFields as $field)
			{
				$field->setIsFresh(null);
			}

			$footHtml = Craft::$app->getView()->clearJsBuffer();

			$blockTypes[] = [
				'handle'   => $blockType->handle,
				'name'     => Craft::t('app', $blockType->name),
				'bodyHtml' => $bodyHtml,
				'footHtml' => $footHtml,
			];
		}

		Craft::$app->getView()->setNamespace($originalNamespace);

		return $blockTypes;
	}
}
