<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use Craft;
use craft\app\db\ActiveRecord;
use craft\app\db\StructuredElementQuery;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Class StructureElement record.
 *
 * @property integer $id ID
 * @property integer $structureId Structure ID
 * @property integer $elementId Element ID
 * @property integer $root Root
 * @property integer $lft Lft
 * @property integer $rgt Rgt
 * @property integer $level Level
 * @property ActiveQueryInterface $structure Structure
 * @property ActiveQueryInterface $element Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class StructureElement extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['root'], 'number', 'min' => 0, 'max' => 4294967295, 'integerOnly' => true],
			[['lft'], 'number', 'min' => 0, 'max' => 4294967295, 'integerOnly' => true],
			[['rgt'], 'number', 'min' => 0, 'max' => 4294967295, 'integerOnly' => true],
			[['level'], 'number', 'min' => 0, 'max' => 65535, 'integerOnly' => true],
			[['structureId'], 'unique', 'targetAttribute' => ['structureId', 'elementId']],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%structureelements}}';
	}

	/**
	 * @inheritdoc
	 */
	public static function find()
	{
		return Craft::createObject(StructuredElementQuery::className(), [get_called_class()]);
	}

	/**
	 * Returns the structure element’s structure.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getStructure()
	{
		return $this->hasOne(Structure::className(), ['id' => 'structureId']);
	}

	/**
	 * Returns the structure element’s element.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'elementId']);
	}


	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'tree' => [
				'class' => NestedSetsBehavior::className(),
				'treeAttribute' => 'root',
				'leftAttribute' => 'lft',
				'rightAttribute' => 'rgt',
				'depthAttribute' => 'level',
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function transactions()
	{
		return [
			static::SCENARIO_DEFAULT => static::OP_ALL,
		];
	}
}
