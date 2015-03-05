<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\db\Query;
use craft\app\enums\AttributeType;
use craft\app\helpers\DbHelper;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\Field as FieldModel;

/**
 * The MatrixBlock class is responsible for implementing and defining Matrix blocks as a native element type
 * in Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlock extends Element
{
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

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ElementInterface::hasContent()
	 *
	 * @return bool
	 */
	public static function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::isLocalized()
	 *
	 * @return bool
	 */
	public static function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public static function defineCriteriaAttributes()
	{
		return [
			'fieldId'     => AttributeType::Number,
			'order'       => [AttributeType::String, 'default' => 'matrixblocks.sortOrder'],
			'ownerId'     => AttributeType::Number,
			'ownerLocale' => AttributeType::Locale,
			'type'        => AttributeType::Mixed,
		];
	}

	/**
	 * @inheritDoc ElementInterface::getContentTableForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return string
	 */
	public static function getContentTableForElementsQuery(ElementCriteriaModel $criteria)
	{
		if (!$criteria->fieldId && $criteria->id && is_numeric($criteria->id))
		{
			$criteria->fieldId = (new Query())
				->select('fieldId')
				->from('{{%matrixblocks}}')
				->where('id = :id', [':id' => $criteria->id])
				->scalar();
		}

		if ($criteria->fieldId && is_numeric($criteria->fieldId))
		{
			$matrixField = Craft::$app->fields->getFieldById($criteria->fieldId);

			if ($matrixField)
			{
				return Craft::$app->matrix->getContentTableName($matrixField);
			}
		}
	}

	/**
	 * @inheritDoc ElementInterface::getFieldsForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return FieldModel[]
	 */
	public static function getFieldsForElementsQuery(ElementCriteriaModel $criteria)
	{
		$fields = [];

		foreach (Craft::$app->matrix->getBlockTypesByFieldId($criteria->fieldId) as $blockType)
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

	/**
	 * @inheritDoc ElementInterface::modifyElementsQuery()
	 *
	 * @param Query                $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public static function modifyElementsQuery(Query $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('matrixblocks.fieldId, matrixblocks.ownerId, matrixblocks.ownerLocale, matrixblocks.typeId, matrixblocks.sortOrder')
			->innerJoin('{{%matrixblocks}} matrixblocks', 'matrixblocks.id = elements.id');

		if ($criteria->fieldId)
		{
			$query->andWhere(DbHelper::parseParam('matrixblocks.fieldId', $criteria->fieldId, $query->params));
		}

		if ($criteria->ownerId)
		{
			$query->andWhere(DbHelper::parseParam('matrixblocks.ownerId', $criteria->ownerId, $query->params));
		}

		if ($criteria->ownerLocale)
		{
			$query->andWhere(DbHelper::parseParam('matrixblocks.ownerLocale', $criteria->ownerLocale, $query->params));
		}

		if ($criteria->type)
		{
			$query->innerJoin('{{%matrixblocktypes}} matrixblocktypes', 'matrixblocktypes.id = matrixblocks.typeId');
			$query->andWhere(DbHelper::parseParam('matrixblocktypes.handle', $criteria->type, $query->params));
		}
	}

	/**
	 * @inheritDoc ElementInterface::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public static function populateElementModel($row)
	{
		return MatrixBlock::populateModel($row);
	}

	// Instance Methods
	// -------------------------------------------------------------------------

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
