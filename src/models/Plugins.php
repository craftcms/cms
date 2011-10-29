<?php

/**
 * This is the model class for table "{{_plugins}}".
 *
 * The followings are the available columns in table '{{_plugins}}':
 * @property string $name
 * @property string $version
 * @property integer $enabled
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property PluginSettings[] $pluginsettings
 */
class Plugins extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Plugins the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{_plugins}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, version', 'required'),
			array('enabled, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>50),
			array('version', 'length', 'max'=>15),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('name, version, enabled, date_created, date_updated, uid', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'pluginsettings' => array(self::HAS_MANY, 'PluginSettings', 'plugin_name'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'name' => 'Name',
			'version' => 'Version',
			'enabled' => 'Enabled',
			'date_created' => 'Date Created',
			'date_updated' => 'Date Updated',
			'uid' => 'Uid',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('name',$this->name,true);
		$criteria->compare('version',$this->version,true);
		$criteria->compare('enabled',$this->enabled);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
