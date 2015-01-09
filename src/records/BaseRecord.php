<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\dates\DateTime;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\ModelHelper;
use craft\app\helpers\StringHelper;

/**
 * Active Record base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseRecord extends \CActiveRecord
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
	 * Constructor
	 *
	 * @param string $scenario
	 *
	 * @return BaseRecord
	 */
	public function __construct($scenario = 'insert')
	{
		// If Craft isn't installed, this model's table won't exist yet, so just create an instance of the class,
		// for use by the installer
		if ($scenario !== 'install')
		{
			parent::__construct($scenario);
		}
	}

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		ModelHelper::populateAttributeDefaults($this);

		$this->attachEventHandler('onAfterFind', [$this, 'prepAttributesForUse']);
		$this->attachEventHandler('onBeforeSave', [$this, 'prepAttributesForSave']);
		$this->attachEventHandler('onAfterSave', [$this, 'prepAttributesForUse']);
	}

	/**
	 * Returns the name of the associated database table.
	 *
	 * @return string
	 */
	abstract public function getTableName();

	/**
	 * Returns an instance of the specified model
	 *
	 * @param string $class
	 *
	 * @return BaseRecord|object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model(get_called_class());
	}

	/**
	 * Returns the table's primary key.
	 *
	 * @return mixed
	 */
	public function primaryKey()
	{
		return 'id';
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
	 * Defines this model's relations to other models.
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [];
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

			$this->setAttribute($name, ModelHelper::packageAttributeValue($value, true));
		}

		// Populate dateCreated and uid if this is a new record
		if ($this->isNewRecord())
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
					if (is_string($value) && mb_strlen($value) && ($value[0] == '[' || $value[0] == '{'))
					{
						$this->setAttribute($name, JsonHelper::decode($value));
					}

					break;
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		$scopes = [];

		// Add ordered() scope if this model has a sortOrder attribute
		$attributes = $this->getAttributeConfigs();

		if (isset($attributes['sortOrder']))
		{
			$scopes['ordered'] = ['order' => 'sortOrder'];
		}

		return $scopes;
	}

	/**
	 * Creates the model's table.
	 *
	 * @return null
	 */
	public function createTable()
	{
		$table = $this->getTableName();
		$indexes = $this->defineIndexes();
		$attributes = $this->getAttributeConfigs();
		$columns = [];

		// Add any Foreign Key columns
		foreach ($this->getBelongsToRelations() as $name => $config)
		{
			$columnName = $config[2];

			// Is the record already defining this column?
			if (isset($attributes[$columnName]))
			{
				continue;
			}

			$required = !empty($config['required']);
			$columns[$columnName] = ['column' => ColumnType::Int, 'required' => $required];

			// Add unique index for this column?
			// (foreign keys already get indexed, so we're only concerned with whether it should be unique)
			if (!empty($config['unique']))
			{
				$indexes[] = ['columns' => [$columnName], 'unique' => true];
			}
		}

		// Add all other columns
		foreach ($attributes as $name => $config)
		{
			// Add (unique) index for this column?
			$indexed = !empty($config['indexed']);
			$unique = !empty($config['unique']);

			if ($unique || $indexed)
			{
				$indexes[] = ['columns' => [$name], 'unique' => $unique];
			}

			$columns[$name] = $config;
		}

		$pk = $this->primaryKey();

		if (isset($columns[$pk]))
		{
			$columns[$pk]['primaryKey'] = true;
			$addIdColumn = false;
		}
		else
		{
			$addIdColumn = true;
		}

		// Create the table
		Craft::$app->db->createCommand()->createTable($table, $columns, null, $addIdColumn);

		// Create the indexes
		foreach ($indexes as $index)
		{
			$columns = ArrayHelper::toArray($index['columns']);
			$unique = !empty($index['unique']);
			Craft::$app->db->createCommand()->createIndex($table, implode(',', $columns), $unique);
		}
	}

	/**
	 * Returns the BELONGS_TO relations.
	 *
	 * @return array
	 */
	public function getBelongsToRelations()
	{
		$belongsTo = [];

		foreach ($this->defineRelations() as $name => $config)
		{
			if ($config[0] == static::BELONGS_TO)
			{
				$this->_normalizeRelation($name, $config);
				$belongsTo[$name] = $config;
			}
		}

		return $belongsTo;
	}

	/**
	 * Drops the model's table.
	 *
	 * @return null
	 */
	public function dropTable()
	{
		$table = $this->getTableName();

		// Does the table exist?
		if (Craft::$app->db->tableExists($table))
		{
			Craft::$app->db->createCommand()->dropTable($table);
		}
	}

	/**
	 * Adds foreign keys to the model's table.
	 *
	 * @return null
	 */
	public function addForeignKeys()
	{
		$table = $this->getTableName();

		foreach ($this->getBelongsToRelations() as $name => $config)
		{
			$otherRecord = new $config[1];
			$otherTable = $otherRecord->getTableName();
			$otherPk = $otherRecord->primaryKey();

			if (isset($config['onDelete']))
			{
				$onDelete = $config['onDelete'];
			}
			else
			{
				if (empty($config['required']))
				{
					$onDelete = static::SET_NULL;
				}
				else
				{
					$onDelete = null;
				}
			}

			if (isset($config['onUpdate']))
			{
				$onUpdate = $config['onUpdate'];
			}
			else
			{
				$onUpdate = null;
			}

			Craft::$app->db->createCommand()->addForeignKey($table, $config[2], $otherTable, $otherPk, $onDelete, $onUpdate);
		}
	}

	/**
	 * Drops the foreign keys from the model's table.
	 *
	 * @return null
	 */
	public function dropForeignKeys()
	{
		$table = $this->getTableName();

		// Does the table exist?
		if (Craft::$app->db->tableExists($table, true))
		{
			foreach ($this->getBelongsToRelations() as $name => $config)
			{
				// Make sure the record's table exists
				$otherRecord = new $config[1];

				if (Craft::$app->db->tableExists($otherRecord->getTableName()))
				{
					Craft::$app->db->createCommand()->dropForeignKey($table, $config[2]);
				}
			}
		}
	}

	// Rename a couple CActiveRecord functions

	/**
	 * @return bool
	 */
	public function isNewRecord()
	{
		return $this->getIsNewRecord();
	}

	/**
	 * @param mixed $id
	 * @param mixed $condition
	 * @param array $params
	 *
	 * @return BaseRecord
	 */
	public function findById($id, $condition = '', $params = [])
	{
		return $this->findByPk($id, $condition, $params);
	}

	/**
	 * @param mixed $id
	 * @param mixed $condition
	 * @param array $params
	 *
	 * @return BaseRecord[]
	 */
	public function findAllById($id, $condition = '', $params = [])
	{
		return $this->findAllByPk($id, $condition, $params);
	}

	// CModel and CActiveRecord methods

	/**
	 * Returns the name of the associated database table.
	 *
	 * @return string
	 */
	public function tableName()
	{
		return '{{'.$this->getTableName().'}}';
	}

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

	/**
	 * Declares the related models.
	 *
	 * @return array
	 */
	public function relations()
	{
		$relations = $this->defineRelations();

		foreach ($relations as $name => &$config)
		{
			$this->_normalizeRelation($name, $config);

			// Unset any keys that CActiveRecord isn't expecting
			unset($config['required'], $config['unique'], $config['onDelete'], $config['onUpdate']);
		}

		return $relations;
	}

	/**
	 * Sets the named attribute value. You may also use $this->AttributeName to set the attribute value.
	 *
	 * @param string $name  The attribute name.
	 * @param mixed  $value The attribute value.
	 *
	 * @return bool Whether the attribute exists and the assignment is conducted successfully.
	 */
	public function setAttribute($name, $value)
	{
		if (property_exists($this, $name))
		{
			$this->$name = $value;
		}
		else if (isset($this->getMetaData()->columns[$name]))
		{
			$this->_attributes[$name] = $value;
		}
		else
		{
			return false;
		}

		return true;
	}

	/**
	 * Adds search criteria based on this model's attributes.
	 *
	 * @return \CActiveDataProvider
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that should not be searched.
		$criteria = new \CDbCriteria;

		foreach (array_keys($this->getAttributeConfigs()) as $name)
		{
			$criteria->compare($name, $this->$name);
		}

		return new \CActiveDataProvider($this, [
			'criteria' => $criteria
		]);
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

	// Private Methods
	// =========================================================================

	/**
	 * Normalizes a relation's config
	 *
	 * @param string $name
	 * @param array  &$config
	 *
	 * @return null
	 */
	private function _normalizeRelation($name, &$config)
	{
		// Add the namespace to the class name
		if (mb_strpos($config[1], '\\') === false)
		{
			$config[1] = __NAMESPACE__.'\\'.$config[1];
		}

		switch ($config[0])
		{
			case static::BELONGS_TO:
			{
				// Add the foreign key
				if (empty($config[2]))
				{
					array_splice($config, 2, 0, $name.'Id');
				}
				break;
			}

			case static::MANY_MANY:
			{
				$config[2] = Craft::$app->db->tablePrefix.$config[2];
				break;
			}
		}
	}
}
