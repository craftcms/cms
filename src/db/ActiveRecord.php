<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use Craft;
use craft\app\dates\DateTime;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\DbHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\ModelHelper;
use craft\app\helpers\StringHelper;

/**
 * Active Record base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
	// Constants
	// =========================================================================

	const RESTRICT = 'RESTRICT';
	const CASCADE = 'CASCADE';
	const NO_ACTION = 'NO ACTION';
	const SET_DEFAULT = 'SET DEFAULT';
	const SET_NULL = 'SET NULL';

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_attributeConfigs;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		ModelHelper::populateAttributeDefaults($this);
	}

	/**
	 * @inheritdoc
	 *
	 * @return string[]
	 */
	public static function primaryKey()
	{
		return ['id'];
	}

	/**
	 * Returns this record's normalized attribute configs.
	 *
	 * @return array
	 */
	public function getAttributeConfigs()
	{
		if (!isset($this->_attributeConfigs))
		{
			$this->_attributeConfigs = [];

			foreach ($this->defineAttributes() as $name => $config)
			{
				$this->_attributeConfigs[$name] = ModelHelper::normalizeAttributeConfig($config);
			}
		}

		return $this->_attributeConfigs;
	}

	/**
	 * Defines this model's database table indexes.
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [];
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		$this->prepAttributesForUse();
		parent::afterFind();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		$this->prepAttributesForSave();
		return parent::beforeSave($insert);
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave($insert, $changedAttributes)
	{
		$this->prepAttributesForUse();
		parent::afterSave($insert, $changedAttributes);
	}

	/**
	 * Prepares the model's attribute values to be saved to the database.
	 *
	 * @return null
	 */
	public function prepAttributesForSave()
	{
		$attributes = $this->getAttributeConfigs();
		$attributes['dateUpdated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];
		$attributes['dateCreated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];

		foreach ($attributes as $name => $config)
		{
			$value = $this->getAttribute($name);

			if ($config['type'] == AttributeType::DateTime)
			{
				// Leaving this in because we want to allow plugin devs to save a timestamp or DateTime object.
				if (DateTimeHelper::isValidTimeStamp($value))
				{
					$value = new DateTime('@'.$value);
				}
			}

			$this->setAttribute($name, DbHelper::prepValue($value));
		}

		// Populate dateCreated and uid if this is a new record
		if ($this->getIsNewRecord())
		{
			$this->dateCreated = DateTimeHelper::currentTimeForDb();
			$this->uid = StringHelper::UUID();
		}

		// Update the dateUpdated
		$this->dateUpdated = DateTimeHelper::currentTimeForDb();
	}

	/**
	 * Return the attribute values to the formats we want to work with in the code.
	 *
	 * @return null
	 */
	public function prepAttributesForUse()
	{
		$attributes = $this->getAttributeConfigs();
		$attributes['dateUpdated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];
		$attributes['dateCreated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];

		foreach ($attributes as $name => $config)
		{
			$value = $this->getAttribute($name);

			switch ($config['type'])
			{
				case AttributeType::DateTime:
				{
					if ($value)
					{
						// TODO: MySQL specific.
						$dateTime = DateTime::createFromFormat(DateTime::MYSQL_DATETIME, $value);

						$this->setAttribute($name, $dateTime);
					}

					break;
				}
				case AttributeType::Mixed:
				{
					if (is_string($value) && StringHelper::length($value) && StringHelper::containsAny($value[0], array('[', '{')))
					{
						$this->setAttribute($name, JsonHelper::decode($value));
					}

					break;
				}
			}
		}
	}

	// Model and ActiveRecord methods

	/**
	 * Returns this model's validation rules.
	 *
	 * @return array
	 */
	public function rules()
	{
		return ModelHelper::getRules($this);
	}

	/**
	 * Returns the attribute labels.
	 *
	 * @return array
	 */
	public function attributeLabels()
	{
		return ModelHelper::getAttributeLabels($this);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Defines this model's attributes.
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [];
	}
}
