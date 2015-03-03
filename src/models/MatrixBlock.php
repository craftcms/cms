<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ElementType;
use craft\app\models\Field as FieldModel;
use craft\app\models\FieldLayout as FieldLayoutModel;
use craft\app\models\MatrixBlockType as MatrixBlockTypeModel;

/**
 * MatrixBlock model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlock extends BaseElementModel
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var boolean Enabled
	 */
	public $enabled = true;

	/**
	 * @var boolean Archived
	 */
	public $archived = false;

	/**
	 * @var string Locale
	 */
	public $locale = 'en-US';

	/**
	 * @var boolean Locale enabled
	 */
	public $localeEnabled = true;

	/**
	 * @var string Slug
	 */
	public $slug;

	/**
	 * @var string URI
	 */
	public $uri;

	/**
	 * @var \DateTime Date created
	 */
	public $dateCreated;

	/**
	 * @var \DateTime Date updated
	 */
	public $dateUpdated;

	/**
	 * @var integer Root
	 */
	public $root;

	/**
	 * @var integer Lft
	 */
	public $lft;

	/**
	 * @var integer Rgt
	 */
	public $rgt;

	/**
	 * @var integer Level
	 */
	public $level;

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
	 * @var string
	 */
	protected $elementType = ElementType::MatrixBlock;

	/**
	 * @var
	 */
	private $_owner;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'slug' => Craft::t('app', 'Slug'),
			'uri' => Craft::t('app', 'URI'),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['dateCreated'], 'craft\\app\\validators\\DateTime'],
			[['dateUpdated'], 'craft\\app\\validators\\DateTime'],
			[['root'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['lft'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['rgt'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['level'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['fieldId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['ownerId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['ownerLocale'], 'craft\\app\\validators\\Locale'],
			[['typeId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sortOrder'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'enabled', 'archived', 'locale', 'localeEnabled', 'slug', 'uri', 'dateCreated', 'dateUpdated', 'root', 'lft', 'rgt', 'level', 'fieldId', 'ownerId', 'ownerLocale', 'typeId', 'sortOrder', 'collapsed'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
	 *
	 * @return FieldLayoutModel|null
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
	 * @inheritDoc BaseElementModel::getLocales()
	 *
	 * @return array
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
	 * @return MatrixBlockTypeModel|null
	 */
	public function getType()
	{
		if ($this->typeId)
		{
			return Craft::$app->matrix->getBlockTypeById($this->typeId);
		}
	}

	/**
	 * Returns the owner.
	 *
	 * @return BaseElementModel|null
	 */
	public function getOwner()
	{
		if (!isset($this->_owner) && $this->ownerId)
		{
			$this->_owner = Craft::$app->elements->getElementById($this->ownerId, null, $this->locale);

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
	 * @param BaseElementModel
	 */
	public function setOwner(BaseElementModel $owner)
	{
		$this->_owner = $owner;
	}

	/**
	 * @inheritDoc BaseElementModel::getContentTable()
	 *
	 * @return string
	 */
	public function getContentTable()
	{
		return Craft::$app->matrix->getContentTableName($this->_getField());
	}

	/**
	 * @inheritDoc BaseElementModel::getFieldColumnPrefix()
	 *
	 * @return string
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
	 * @return FieldModel
	 */
	private function _getField()
	{
		return Craft::$app->fields->getFieldById($this->fieldId);
	}
}
