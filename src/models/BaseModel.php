<?php
namespace Blocks;

/**
 * @abstract
 */
abstract class BaseModel extends \CActiveRecord
{
	protected $tableName;
	protected $attributes = array();
	protected $belongsTo = array();
	protected $hasBlocks = array();
	protected $hasContent = array();
	protected $hasMany = array();
	protected $hasOne = array();
	protected $indexes = array();
	protected $_tableName;

	/**
	 * Constructor
	 * @param string $scenario
	 */
	public function __construct($scenario = 'insert')
	{
		// If Blocks isn't installed, this model's table won't exist yet,
		// so just create an instance of the class, for use by the installer
		if (!Blocks::app()->isInstalled)
		{
			// Just do the bare minimum of constructor-type stuff.
			// Maybe init() is all that's necessary?
			$this->init();
		}
		else
			parent::__construct($scenario);
	}

	/**
	 * @return string The model's table name
	 */
	public function tableName()
	{
		return '{{'.$this->getTableName().'}}';
	}

	/**
	 * Get the model's table name (without the curly brackets)
	 * @return string The table name
	 * @access protected
	 */
	protected function getTableName()
	{
		if (!isset($this->_tableName))
		{
			if (isset($this->tableName))
				$this->_tableName = $this->tableName;
			else
				$this->_tableName = strtolower(get_class($this));
		}

		return $this->_tableName;
	}

	/**
	 * @return array Validation rules for model's attributes
	 */
	public function rules()
	{
		$rules = array();

		$required = array();
		$emails = array();
		$strictLengths = array();
		$minLengths = array();
		$maxLengths = array();

		$numberTypes = array(AttributeType::TinyInt, AttributeType::SmallInt, AttributeType::MediumInt, AttributeType::Int, AttributeType::BigInt, AttributeType::Float, AttributeType::Decimal);
		$integerTypes = array(AttributeType::TinyInt, AttributeType::SmallInt, AttributeType::MediumInt, AttributeType::Int, AttributeType::BigInt);

		foreach ($this->attributes as $name => $settings)
		{
			// Catch email addresses before running normalizeAttributeSettings, since 'type' will get changed to VARCHAR
			if (isset($settings['type']) && $settings['type'] == AttributeType::Email)
				$emails[] = $name;

			$settings = DatabaseHelper::normalizeAttributeSettings($settings);

			// Only enforce 'required' validation if there's no default value
			if (isset($settings['required']) && $settings['required'] === true && !isset($settings['default']))
				$required[] = $name;

			// Numbers
			if (in_array($settings['type'], $numberTypes))
			{
				$rule = array($name);

				if (isset($settings['min']) && is_numeric($settings['min']))
					$rule['min'] = $settings['min'];

				if (isset($settings['max']) && is_numeric($settings['max']))
					$rule['max'] = $settings['max'];

				if (in_array($settings['type'], $integerTypes))
					$rule['integerOnly'] = true;

				$rules[] = $rule;
			}

			// Enum attribute values
			if ($settings['type'] == AttributeType::Enum)
			{
				$values = ArrayHelper::stringToArray($settings['values']);
				$rules[] = array($name, 'in', 'range' => $values);
			}

			// Strict, min, and max lengths
			if (isset($settings['length']) && is_numeric($settings['length']))
				$strictLengths[(string)$settings['length']][] = $name;
			else
			{
				// Only worry about min- and max-lengths if a strict length isn't set
				if (isset($settings['minLength']) && is_numeric($settings['minLength']))
					$minLengths[(string)$settings['minLength']][] = $name;

				if (isset($settings['maxLength']) && is_numeric($settings['maxLength']))
					$maxLengths[(string)$settings['maxLength']][] = $name;
			}
		}

		if ($required)
			$rules[] = array(implode(',', $required), 'required');

		if ($emails)
			$rules[] = array(implode(',', $emails), 'email');

		if ($strictLengths)
		{
			foreach ($strictLengths as $strictLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'is' => (int)$strictLength);
			}
		}

		if ($minLengths)
		{
			foreach ($minLengths as $minLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'min' => (int)$minLength);
			}
		}

		if ($maxLengths)
		{
			foreach ($maxLengths as $maxLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'max' => (int)$maxLength);
			}
		}

		$rules[] = array(implode(',', array_keys($this->attributes)), 'safe', 'on' => 'search');

		return $rules;
	}

	/**
	 * @return array Relational rules
	 */
	public function relations()
	{
		$relations = array();

		foreach ($this->hasBlocks as $key => $settings)
		{
			$relations[$key] = $this->generateJoinThroughRelation('ContentBlock', 'block_id', $settings);
		}

		foreach ($this->hasContent as $key => $settings)
		{
			$relations[$key] = $this->generateJoinThroughRelation('Content', 'content_id', $settings);
		}

		foreach ($this->hasMany as $key => $settings)
		{
			$relations[$key] = $this->generateHasXRelation(self::HAS_MANY, $settings);
		}

		foreach ($this->hasOne as $key => $model)
		{
			$relations[$key] = $this->generateHasXRelation(self::HAS_ONE, $settings);
		}

		foreach ($this->belongsTo as $key => $model)
		{
			$relations[$key] = array(self::BELONGS_TO, $model, $key.'_id');
		}

		return $relations;
	}

	/**
	 * Generates HAS_MANY relations to a model through another model
	 * @access protected
	 * @param string $model The destination model
	 * @param string $fk2 The join table's foreign key to the destination model
	 * @param array $settings The initial model's settings for the relation
	 * @return The CActiveRecord relation
	 */
	protected function generateJoinThroughRelation($model, $fk2, $settings)
	{
		return array(self::HAS_MANY, $model, array($settings['foreignKey'].'_id' => $fk2), 'through' => $settings['through']);
	}

	/**
	 * Generates HAS_MANY and HAS_ONE relations
	 * @access protected
	 * @param string $relationType The type of relation to generate (self::HAS_MANY or self::HAS_ONE)
	 * @param array $settings The relation settings
	 * @return array The CActiveRecord relation
	 */
	protected function generateHasXRelation($relationType, $settings)
	{
		if (is_array($settings['foreignKey']))
		{
			$fk = array();
			foreach ($settings['foreignKey'] as $fk1 => $fk2)
			{
				$fk[$fk1.'_id'] = $fk2.'_id';
			}
		}
		else
		{
			$fk = $settings['foreignKey'].'_id';
		}

		$relation = array($relationType, $settings['model'], $fk);

		if (isset($settings['through']))
			$relation['through'] = $settings['through'];

		return $relation;
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider The data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new \CDbCriteria;

		foreach (array_keys($this->attributes) as $attributeName)
		{
			$criteria->compare($attributeName, $this->$attributeName);
		}

		return new \CActiveDataProvider($this, array(
			'criteria' => $criteria
		));
	}

	/**
	 * Saves the record, whether it's new or existing
	 *
	 * @param bool $runValidation
	 * @param null $attributes
	 * @return bool
	 */
	function save($runValidation = true, $attributes = null)
	{
		if ($this->isNewRecord)
		   return parent::save($runValidation, $attributes);

		if (!$runValidation || $this->validate())
		{
			return $this->update($attributes);
		}

		return false;
	}

	/**
	 * Creates the model's table
	 */
	public function createTable()
	{
		$connection = Blocks::app()->db;
		$tablePrefix = Blocks::app()->getDbConfig('tablePrefix');
		$tableName = $this->getTableName();

		// Make sure that the table doesn't already exist
		if ($connection->schema->getTable('{{'.$tableName.'}}') !== null)
			throw new Exception($tableName.' already exists.');

		// Begin assembling the columns and indexes
		$columns['id'] = AttributeType::PK;
		$indexes = array_merge($this->indexes);

		// Add any Foreign Key columns
		foreach ($this->belongsTo as $name => $settings)
		{
			$required = isset($settings['required']) ? $settings['required'] : false;
			$settings = array('type' => AttributeType::Int, 'required' => $required);
			$columns[$name.'_id'] = DatabaseHelper::generateColumnDefinition($settings);

			// Add unique index for this column?
			// (foreign keys already get indexed, so we're only concerned with whether it should be unique)
			if (isset($settings['unique']) && $settings['unique'] === true)
				$indexes[] = array('columns' => array($name.'_id'), 'unique' => $settings['unique']);
		}

		// Add all other columns
		foreach ($this->attributes as $name => $settings)
		{
			$settings = DatabaseHelper::normalizeAttributeSettings($settings);

			// Add (unique) index for this column?
			$unique = (isset($settings['unique']) && $settings['unique'] === true);
			if ($unique || (isset($settings['indexed']) && $settings['indexed'] === true))
				$indexes[] = array('columns' => array($name), 'unique' => $unique);

			$columns[$name] = DatabaseHelper::generateColumnDefinition($settings);
		}

		// Add the remaining global columns
		$columns['date_created'] = DatabaseHelper::generateColumnDefinition(array('type' => AttributeType::Int, 'required' => true));
		$columns['date_updated'] = DatabaseHelper::generateColumnDefinition(array('type' => AttributeType::Int, 'required' => true));
		$columns['uid']          = DatabaseHelper::generateColumnDefinition(array('type' => AttributeType::Varchar, 'maxLength' => 36, 'required' => true));

		// Create the table
		$connection->createCommand()->createTable('{{'.$tableName.'}}', $columns);

		// Create the indexes
		foreach ($this->indexes as $i => $index)
		{
			$columns = ArrayHelper::stringToArray($index['columns']);
			$unique = (isset($index['unique']) && $index['unique'] === true);
			$name = "{$tablePrefix}_{$tableName}_".implode('_', $columns).($unique ? '_unique' : '').'_idx';

			$connection->createCommand()->createIndex($name, '{{'.$tableName.'}}', implode(',', $columns), $unique);
		}

		// Add the INSERT and UPDATE triggers
		DatabaseHelper::createInsertAuditTrigger($tableName);
		DatabaseHelper::createUpdateAuditTrigger($tableName);
	}

	/**
	 * Adds foreign keys to the model's table
	 */
	public function addForeignKeys()
	{
		$connection = Blocks::app()->db;
		$tablePrefix = Blocks::app()->getDbConfig('tablePrefix');
		$tableName = $this->getTableName();

		foreach ($this->belongsTo as $name => $settings)
		{
			$otherModelClass = __NAMESPACE__.'\\'.$settings['model'];
			$otherModel = new $otherModelClass;
			$otherTableName = $otherModel->getTableName();
			$fkName = "{$tablePrefix}_{$tableName}_{$otherTableName}_fk";
			$connection->createCommand()->addForeignKey($fkName, '{{'.$tableName.'}}', $name.'_id', '{{'.$otherTableName.'}}', 'id', 'NO ACTION', 'NO ACTION');
		}
	}

	/**
	 * Drops the foreign keys from the model's table
	 */
	public function dropForeignKeys()
	{
		$connection = Blocks::app()->db;
		$tablePrefix = Blocks::app()->getDbConfig('tablePrefix');
		$tableName = $this->getTableName();

		foreach ($this->belongsTo as $name => $settings)
		{
			$otherModelClass = __NAMESPACE__.'\\'.$settings['model'];
			$otherModel = new $otherModelClass;
			$otherTableName = $otherModel->getTableName();
			$fkName = "{$tablePrefix}_{$tableName}_{$otherTableName}_fk";
			$connection->createCommand()->dropForeignKey($fkName, '{{'.$tableName.'}}');
		}
	}

	/**
	 * Drops the model's table
	 */
	public function dropTable()
	{
		$connection = Blocks::app()->db;
		$tableName = $this->getTableName();

		if ($connection->schema->getTable($tableName) !== null)
		{
			$connection->createCommand()->dropTable($tableName);
		}
	}

	/**
	 * Returns an instance of the specified model
	 *
	 * @static
	 * @param string $class
	 * @return object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model(get_called_class());
	}
}
