<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\db\ActiveRecord;
use craft\app\db\TaskQuery;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use craft\app\enums\TaskStatus;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Class Task record.
 *
 * @var integer $id ID
 * @var integer $root Root
 * @var integer $lft Lft
 * @var integer $rgt Rgt
 * @var integer $level Level
 * @var integer $currentStep Current step
 * @var integer $totalSteps Total steps
 * @var string $status Status
 * @var string $type Type
 * @var string $description Description
 * @var array $settings Settings

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Task extends ActiveRecord
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
			[['currentStep'], 'number', 'min' => 0, 'max' => 4294967295, 'integerOnly' => true],
			[['totalSteps'], 'number', 'min' => 0, 'max' => 4294967295, 'integerOnly' => true],
			[['status'], 'in', 'range' => ['pending', 'error', 'running']],
			[['type'], 'required'],
			[['type'], 'string', 'max' => 150],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%tasks}}';
	}

	/**
	 * @inheritDoc
	 */
	public static function find()
	{
		return Craft::createObject(TaskQuery::className(), [get_called_class()]);
	}


	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	public function transactions()
	{
		return [
			static::SCENARIO_DEFAULT => static::OP_ALL,
		];
	}
}
