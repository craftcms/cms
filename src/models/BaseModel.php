<?php
namespace Blocks;

/**
 * Model base class
 *
 * @abstract
 */
abstract class BaseModel extends \CActiveRecord
{
	/**
	 * Constructor
	 * @param string $scenario
	 */
	function __construct($scenario = 'insert')
	{
		$this->attachEventHandler('onBeforeSave', array($this, 'populateAuditProperties'));

		// If @@@productDisplay@@@ isn't installed, this model's table won't exist yet,
		// so just create an instance of the class, for use by the installer
		if (!blx()->getIsInstalled())
		{
			// Just do the bare minimum of constructor-type stuff.
			// Maybe init() is all that's necessary?
			$this->init();
		}
		else
		{
			parent::__construct($scenario);
			$this->populatePropertyDefaults();
		}
	}

	/**
	 * Returns the name of the associated database table.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		// Default to the lowercase classname, sans namespace
		return strtolower(substr(get_class($this), strlen(__NAMESPACE__) + 1));
	}

	/**
	 * Returns a list of this model's properties.
	 *
	 * @return array
	 */
	protected function getProperties()
	{
		return array();
	}

	/**
	 * Returns a list of this model's active record relations.
	 *
	 * @return array
	 */
	protected function getRelations()
	{
		return array();
	}

	/**
	 * Returns a list of this model's indexes.
	 *
	 * @return array
	 */
	protected function getIndexes()
	{
		return array();
	}

	/**
	 * Creates the model's table
	 */
	public function createTable()
	{
		$table = $this->getTableName();
		$indexes = $this->getIndexes();
		$columns = array();

		// Add any Foreign Key columns
		foreach ($this->_getBelongsToRelations() as $name => $config)
		{
			$required = isset($config['required']) ? $config['required'] : false;
			$columns[$config[2]] = array('type' => PropertyType::Int, 'required' => $required);

			// Add unique index for this column?
			// (foreign keys already get indexed, so we're only concerned with whether it should be unique)
			if (isset($config['unique']) && $config['unique'] === true)
				$indexes[] = array('columns' => array($config[2]), 'unique' => true);
		}

		// Add all other columns
		foreach ($this->getProperties() as $name => $config)
		{
			$config = DatabaseHelper::normalizePropertyConfig($config);

			// Add (unique) index for this column?
			$unique = (isset($config['unique']) && $config['unique'] === true);
			if ($unique || (isset($config['indexed']) && $config['indexed'] === true))
				$indexes[] = array('columns' => array($name), 'unique' => true);

			$columns[$name] = $config;
		}

		// Create the table
		blx()->db->createCommand()->createTable($table, $columns);

		// Create the indexes
		foreach ($indexes as $index)
		{
			$columns = ArrayHelper::stringToArray($index['columns']);
			$unique = (isset($index['unique']) && $index['unique'] === true);
			$name = "{$table}_".implode('_', $columns).($unique ? '_unique' : '').'_idx';
			blx()->db->createCommand()->createIndex($name, $table, implode(',', $columns), $unique);
		}
	}

	/**
	 * Returns the BELONGS_TO relations.
	 *
	 * @access private
	 * @return array
	 */
	private function _getBelongsToRelations()
	{
		$belongsTo = array();
		foreach ($this->getRelations() as $name => $config)
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
	 * Drops the model's table
	 */
	public function dropTable()
	{
		$table = $this->getTableName();
		if (blx()->db->getSchema()->getTable($table) !== null)
			blx()->db->createCommand()->dropTable($table);
	}

	/**
	 * Adds foreign keys to the model's table
	 */
	public function addForeignKeys()
	{
		$table = $this->getTableName();

		foreach ($this->_getBelongsToRelations() as $name => $config)
		{
			$otherModel = new $config[1];
			$otherTable = $otherModel->getTableName();
			$fkName = "{$table}_{$otherTable}_fk";
			blx()->db->createCommand()->addForeignKey($fkName, $table, $config[2], $otherTable, 'id');
		}
	}

	/**
	 * Drops the foreign keys from the model's table
	 */
	public function dropForeignKeys()
	{
		$table = $this->getTableName();

		foreach ($this->_getBelongsToRelations() as $name => $config)
		{
			$otherModel = new $config[1];
			$otherTable = $otherModel->getTableName();
			$fkName = "{$table}_{$otherTable}_fk";
			blx()->db->createCommand()->dropForeignKey($fkName, $table);
		}
	}

	/**
	 * Gets a record by its ID.
	 *
	 * @param $id
	 * @param string $condition
	 * @param array $params
	 * @return BaseModel
	 */
	public function findById($id, $condition = '', $params = array())
	{
		return $this->findByPk($id, $condition, $params);
	}

	/**
	 * Populates any default values that are set on the model's properties.
	 */
	public function populatePropertyDefaults()
	{
		foreach ($this->getProperties() as $name => $config)
		{
			$config = DatabaseHelper::normalizePropertyConfig($config);
			if (isset($config['default']))
				$this->_attributes[$name] = $config['default'];
		}
	}

	/**
	 * If it is a new active record instance, will populate date_created with the current UTC unix timestamp and a new GUID
	 * for uid. If it is an existing record, will populate date_updated with the current UTC unix timestamp.
	 */
	public function populateAuditProperties()
	{
		if ($this->getIsNewRecord())
		{
			$this->date_created = DateTimeHelper::currentTime();
			$this->uid = StringHelper::UUID();
		}

		$this->date_updated = DateTimeHelper::currentTime();
	}


	/* CModel and CActiveRecord methods */


	/**
	 * Returns an instance of the specified model
	 * @static
	 * @param string $class
	 * @return \CActiveRecord|object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model(get_called_class());
	}

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
	 * Returns the validation rules for properties.
	 *
	 * @return array
	 */
	public function rules()
	{
		return ModelHelper::createRules($this->getProperties(), $this->getIndexes());
	}

	/**
	 * Declares the related models.
	 *
	 * @return array
	 */
	public function relations()
	{
		$relations = $this->getRelations();
		foreach ($relations as $name => &$config)
		{
			$this->_normalizeRelation($name, $config);

			// Unset any keys that CActiveRecord isn't expecting
			unset($config['required'], $config['unique']);
		}
		return $relations;
	}

	/**
	 * Normalizes a relation's config
	 *
	 * @param string $name
	 * @param array &$config
	 */
	private function _normalizeRelation($name, &$config)
	{
		// Add the namespace to the class name
		if (strpos($config[1], '\\') === false)
			$config[1] = __NAMESPACE__.'\\'.$config[1];

		// Add the foreign key to BELONGS_TO relations
		if ($config[0] == static::BELONGS_TO && empty($config[2]))
			array_splice($config, 2, 0, $name.'_id');
	}

	/**
	 * Adds search criteria based on the properties.
	 *
	 * @return \CActiveDataProvider
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove properties that should not be searched.
		$criteria = new \CDbCriteria;

		foreach (array_keys($this->getProperties()) as $name)
		{
			$criteria->compare($name, $this->$name);
		}

		return new \CActiveDataProvider($this, array(
			'criteria' => $criteria
		));
	}
}
