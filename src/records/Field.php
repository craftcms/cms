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

/**
 * Class Field record.
 *
 * @property integer $id ID
 * @property integer $groupId Group ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $context Context
 * @property string $instructions Instructions
 * @property boolean $translatable Translatable
 * @property string $type Type
 * @property array $settings Settings
 * @property ActiveQueryInterface $group Group
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Field extends ActiveRecord
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	protected $reservedHandleWords = [
		'archived',
		'children',
		'dateCreated',
		'dateUpdated',
		'enabled',
		'id',
		'link',
		'locale',
		'next',
		'parents',
		'prev',
		'siblings',
		'sortOrder',
		'uid',
		'uri',
		'url',
		'ref',
		'slug',
		'status',
		'title',
		'prev',
		'next',
		'contentTable'
	];

	/**
	 * @var
	 */
	private $_oldHandle;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		// TODO: MySQL specific
		$maxHandleLength = 64 - strlen(Craft::$app->getContent()->fieldColumnPrefix);

		return [
			[['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['archived', 'children', 'dateCreated', 'dateUpdated', 'enabled', 'id', 'link', 'locale', 'parents', 'siblings', 'uid', 'uri', 'url', 'ref', 'status', 'title']],
			[['handle'], 'unique', 'targetAttribute' => ['handle', 'context']],
			[['name', 'handle', 'context', 'type'], 'required'],
			[['name'], 'string', 'max' => 255],
			[['handle'], 'string', 'max' => $maxHandleLength],
			[['type'], 'string', 'max' => 150],
		];
	}

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		parent::init();

		// Store the old handle in case it's ever requested.
		//$this->attachEventHandler('onAfterFind', [$this, 'storeOldHandle']);
	}

	/**
	 * Store the old handle.
	 *
	 * @return null
	 */
	public function storeOldHandle()
	{
		$this->_oldHandle = $this->handle;
	}

	/**
	 * Returns the old handle.
	 *
	 * @return string
	 */
	public function getOldHandle()
	{
		return $this->_oldHandle;
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%fields}}';
	}

	/**
	 * Returns the field’s group.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getGroup()
	{
		return $this->hasOne(FieldGroup::className(), ['id' => 'groupId']);
	}
}
