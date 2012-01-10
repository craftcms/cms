<?php

abstract class BlocksModel extends CActiveRecord
{
	protected static $hasSettings = false;
	protected static $hasContent = false;
	protected static $hasCustomBlocks = false;

	protected static $hasMany = array();
	protected static $hasOne = array();
	protected static $belongsTo = array();

	protected static $attributes = array();

	/**
	 * @return object An instance of the specified model
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		if (version_compare(Blocks::app()->config->getLocalPHPVersion(), '5.3.0', '>='))
			return parent::model(get_called_class());
		else
			return parent::model(__CLASS__);
	}

	/**
	 * @return bool Whether this model has settings (stored in blx_blocksmodelclass_settings)
	 */
	public function getHasSettings()
	{
		return static::$hasSettings;
	}

	/**
	 * @return bool Whether this model has content (joined to blx_content via blx_blocksmodelclass_content)
	 */
	public function getHasContent()
	{
		return static::$hasContent;
	}

	/**
	 * @return bool Whether this model has custom blocks (joined to blx_contentblocks via blx_blocksmodelclass_blocks)
	 */
	public function getHasCustomBlocks()
	{
		return static::$hasCustomBlocks;
	}

	/**
	 * @return array The model's one-to-many relationships
	 */
	public function getHasMany()
	{
		return static::$hasMany;
	}

	/**
	 * @return array The model's one-to-one relationships
	 */
	public function getHasOne()
	{
		return static::$hasOne;
	}

	/**
	 * @return array One-to-many or one-to-one relationships
	 */
	public function getBelongsTo()
	{
		return static::$belongsTo;
	}

	/**
	 * @return array The model's non-relational attributes
	 */
	public function getAttributes()
	{
		return static::$attributes;
	}

	/**
	 * @return string The associated database table name
	 */
	public function tableName()
	{
		return '{{'.strtolower(get_class($this)).'}}';
	}

	/**
	 * @return array Validation rules for model's attributes
	 */
	public function rules()
	{
		$attributes = $this->getAttributes();

		$required = array();
		$integers = array();
		$maxSizes = array();

		$defaultAttributeSettings = array('type' => AttributeType::String, 'maxSize' => 150, 'required' => false);

		foreach ($attributes as $attributeName => $attributeSettings)
		{
			$attributeSettings = array_merge($defaultAttributeSettings, $attributeSettings);

			if ($attributeSettings['required'] === true)
				$required[] = $attributeName;

			if ($attributeSettings['type'] == AttributeType::Integer)
				$integers[] = $attributeName;

			if ($attributeSettings['type'] == AttributeType::String)
				$maxSizes[(string)$attributeName['maxSize']][] = $attributeName;
		}

		$rules = array();

		if ($required)
			$rules[] = array(implode(', ', $required), 'required');

		if ($integers)
			$rules[] = array(implode(', ', $integers), 'numerical', 'interegOnly' => true);

		if ($maxSizes)
		{
			foreach ($maxSizes as $maxSize => $attributeNames)
			{
				$rules[] = array(implode(', ', $attributeNames), 'length', 'max' => (int)$maxSize);
			}
		}

		$rules[] = implode(', ', array_keys($attributes), 'safe', 'on' => 'search');

		return $rules;
	}

	/**
	 * @return array Relational rules
	 */
	public function relations()
	{
		$relations = array();

		foreach ($this->getHasMany() as $key => $model)
		{
			$model = explode('.', $model);
			$relations[$key] = array(self::HAS_MANY, $model[0], $model[1].'_id');
		}

		foreach ($this->getHasOne() as $key => $model)
		{
			$model = explode('.', $model);
			$relations[$key] = array(self::HAS_ONE, $model[0], $model[1].'_id');
		}

		foreach ($this->getHasAndBelongsToMany() as $key => $model)
		{
			// alphabetize the models
			$models = array(get_class($this), $model);
			sort($models);

			$relations[$key] = array(self::MANY_MANY, $model, strtolower('{{'.$models[0].'_'.$models[1].'}}('.get_class($this).'_id, '.$model.'_id)'));
		}

		foreach ($this->getBelongsTo() as $key => $model)
		{
			$relations[$key] = array(self::BELONGS_TO, $model, $key.'_id');
		}

		return $relations;
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider The data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new CDbCriteria;

		foreach ($this->getAttributes() as $attributeName => $attributeSettings)
		{
			$criteria->compare($attributeName, $this->$attributeName);
		}

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria
		));
	}

	/**
	 * Creates the table(s) necessary for this model to save its data
	 * @static
	 */
	public static function install()
	{
		
	}
}
