<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\db\ActiveRecord;
use craft\app\db\TaskQuery;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Class Task record.
 *
 * @property integer $id ID
 * @property integer $root Root
 * @property integer $lft Lft
 * @property integer $rgt Rgt
 * @property integer $level Level
 * @property integer $currentStep Current step
 * @property integer $totalSteps Total steps
 * @property string $status Status
 * @property string $type Type
 * @property string $description Description
 * @property array $settings Settings
 *
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
	 * @inheritdoc
	 */
	public static function find()
	{
		return Craft::createObject(TaskQuery::className(), [get_called_class()]);
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
