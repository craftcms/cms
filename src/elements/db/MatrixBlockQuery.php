<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\elements\MatrixBlock;
use craft\app\fields\Matrix as MatrixField;
use craft\app\helpers\DbHelper;
use craft\app\models\MatrixBlockType;

/**
 * MatrixBlockQuery represents a SELECT SQL statement for global sets in a way that is independent of DBMS.
 *
 * @property string|string[]|MatrixBlockType $type The handle(s) of the block type(s) that resulting Matrix blocks must have.
 *
 * @method MatrixBlock[]|array all($db=null)
 * @method MatrixBlock|array|null one($db=null)
 * @method MatrixBlock|array|null nth($n,$db=null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlockQuery extends ElementQuery
{
	// Properties
	// =========================================================================

	// General parameters
	// -------------------------------------------------------------------------

	/**
	 * @inheritdoc
	 */
	public $orderBy = 'matrixblocks.sortOrder';

	/**
	 * @var integer|integer[] The field ID(s) that the resulting Matrix blocks must belong to.
	 */
	public $fieldId;

	/**
	 * @var integer|integer[] The owner element ID(s) that the resulting Matrix blocks must belong to.
	 */
	public $ownerId;

	/**
	 * @var string|string[] The locale(s) that the resulting Matrix blocks must have been defined in.
	 */
	public $ownerLocale;

	/**
	 * @var integer|integer[] The block type ID(s) that the resulting Matrix blocks must have.
	 */
	public $typeId;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'type':
			{
				$this->type($value);
				break;
			}
			default:
			{
				parent::__set($name, $value);
			}
		}
	}

	/**
	 * Sets the [[fieldId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function fieldId($value)
	{
		$this->fieldId = $value;
		return $this;
	}

	/**
	 * Sets the [[ownerId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function ownerId($value)
	{
		$this->ownerId = $value;
		return $this;
	}

	/**
	 * Sets the [[ownerLocale]] property.
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function ownerLocale($value)
	{
		$this->ownerLocale = $value;
		return $this;
	}

	/**
	 * Sets the [[typeId]] property based on a given block type(s)’s handle(s).
	 * @param string|string[]|MatrixBlockType $value The property value
	 * @return static The query object itself
	 */
	public function type($value)
	{
		if ($value instanceof MatrixBlockType)
		{
			$this->typeId = $value->id;
		}
		else
		{
			$query = new Query();
			$this->typeId = $query
				->select('id')
				->from('{{%matrixblocktypes}}')
				->where(DbHelper::parseParam('handle', $value, $query->params))
				->column();
		}

		return $this;
	}

	/**
	 * Sets the [[typeId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function typeId($value)
	{
		$this->typeId = $value;
		return $this;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare()
	{
		$this->joinElementTable('matrixblocks');

		// Figure out which content table to use
		$this->contentTable = null;

		if (!$this->fieldId && $this->id && is_numeric($this->id))
		{
			$this->fieldId = (new Query())
				->select('fieldId')
				->from('{{%matrixblocks}}')
				->where('id = :id', [':id' => $this->id])
				->scalar();
		}

		if ($this->fieldId && is_numeric($this->fieldId))
		{
			/** @var MatrixField $matrixField */
			$matrixField = Craft::$app->getFields()->getFieldById($this->fieldId);

			if ($matrixField)
			{
				$this->contentTable = Craft::$app->getMatrix()->getContentTableName($matrixField);
			}
		}

		$this->query->select([
			'matrixblocks.fieldId',
			'matrixblocks.ownerId',
			'matrixblocks.ownerLocale',
			'matrixblocks.typeId',
			'matrixblocks.sortOrder',
		]);

		if ($this->fieldId)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('matrixblocks.fieldId', $this->fieldId, $this->subQuery->params));
		}

		if ($this->ownerId)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('matrixblocks.ownerId', $this->ownerId, $this->subQuery->params));
		}

		if ($this->ownerLocale)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('matrixblocks.ownerLocale', $this->ownerLocale, $this->subQuery->params));
		}

		if ($this->typeId)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('matrixblocks.typeId', $this->typeId, $this->subQuery->params));
		}

		return parent::beforePrepare();
	}
}
