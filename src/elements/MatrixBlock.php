<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\db\Query;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\db\MatrixBlockQuery;
use craft\app\fields\Matrix;
use craft\app\models\MatrixBlockType;

/**
 * MatrixBlock represents a matrix block element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlock extends Element
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Matrix Block');
	}

	/**
	 * @inheritdoc
	 */
	public static function hasContent()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 *
	 * @return MatrixBlockQuery The newly created [[MatrixBlockQuery]] instance.
	 */
	public static function find()
	{
		return new MatrixBlockQuery(get_called_class());
	}

	/**
	 * @inheritdoc
	 */
	public static function getFieldsForElementsQuery(ElementQueryInterface $query)
	{
		$fields = [];

		foreach (Craft::$app->getMatrix()->getBlockTypesByFieldId($query->fieldId) as $blockType)
		{
			$fieldColumnPrefix = 'field_'.$blockType->handle.'_';

			foreach ($blockType->getFields() as $field)
			{
				$field->columnPrefix = $fieldColumnPrefix;
				$fields[] = $field;
			}
		}

		return $fields;
	}

	// Properties
	// =========================================================================

	/**
	 * @var integer Field ID
	 */
	public $fieldId;

	/**
	 * @var integer Owner ID
	 */
	public $ownerId;

	/**
	 * @var string Owner locale
	 */
	public $ownerLocale;

	/**
	 * @var integer Type ID
	 */
	public $typeId;

	/**
	 * @var integer Sort order
	 */
	public $sortOrder;

	/**
	 * @var boolean Collapsed
	 */
	public $collapsed = false;

	/**
	 * @var ElementInterface|Element The owner element
	 */
	private $_owner;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = [['fieldId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['ownerId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['ownerLocale'], 'craft\\app\\validators\\Locale'];
		$rules[] = [['typeId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['sortOrder'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];

		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getFieldLayout()
	{
		$blockType = $this->getType();

		if ($blockType)
		{
			return $blockType->getFieldLayout();
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getLocales()
	{
		// If the Matrix field is translatable, than each individual block is tied to a single locale, and thus aren't
		// translatable. Otherwise all blocks belong to all locales, and their content is translatable.

		if ($this->ownerLocale)
		{
			return [$this->ownerLocale];
		}
		else
		{
			$owner = $this->getOwner();

			if ($owner)
			{
				// Just send back an array of locale IDs -- don't pass along enabledByDefault configs
				$localeIds = [];

				foreach ($owner->getLocales() as $localeId => $localeInfo)
				{
					if (is_numeric($localeId) && is_string($localeInfo))
					{
						$localeIds[] = $localeInfo;
					}
					else
					{
						$localeIds[] = $localeId;
					}
				}

				return $localeIds;
			}
			else
			{
				return [Craft::$app->getI18n()->getPrimarySiteLocaleId()];
			}
		}
	}

	/**
	 * Returns the block type.
	 *
	 * @return MatrixBlockType|null
	 */
	public function getType()
	{
		if ($this->typeId)
		{
			return Craft::$app->getMatrix()->getBlockTypeById($this->typeId);
		}
	}

	/**
	 * Returns the owner.
	 *
	 * @return ElementInterface|Element|null
	 */
	public function getOwner()
	{
		if (!isset($this->_owner) && $this->ownerId)
		{
			$this->_owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->locale);

			if (!$this->_owner)
			{
				$this->_owner = false;
			}
		}

		if ($this->_owner)
		{
			return $this->_owner;
		}
	}

	/**
	 * Sets the owner
	 *
	 * @param Element $owner
	 */
	public function setOwner(Element $owner)
	{
		$this->_owner = $owner;
	}

	/**
	 * @inheritdoc
	 */
	public function getContentTable()
	{
		return Craft::$app->getMatrix()->getContentTableName($this->_getField());
	}

	/**
	 * @inheritdoc
	 */
	public function getFieldColumnPrefix()
	{
		return 'field_'.$this->getType()->handle.'_';
	}

	/**
	 * Returns the field context this element's content uses.
	 *
	 * @return string
	 */
	public function getFieldContext()
	{
		return 'matrixBlockType:'.$this->typeId;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the Matrix field.
	 *
	 * @return Matrix
	 */
	private function _getField()
	{
		return Craft::$app->getFields()->getFieldById($this->fieldId);
	}
}
