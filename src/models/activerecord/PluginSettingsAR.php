<?php

/**
 * This is the model class for table "{{pluginsettings}}".
 *
 * The followings are the available columns in table '{{pluginsettings}}':
 * @property string $plugin_name
 * @property string $key
 * @property string $value
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property Plugins $pluginName
 */
class PluginSettingsAR extends BlocksActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return PluginSettings the static model class
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
		return '{{pluginsettings}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('plugin_name, key', 'required'),
			array('date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('plugin_name', 'length', 'max'=>50),
			array('key', 'length', 'max'=>100),
			array('uid', 'length', 'max'=>36),
			array('value', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('plugin_name, key, value, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'pluginName' => array(self::BELONGS_TO, 'Plugins', 'plugin_name'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'plugin_name' => 'Plugin Name',
			'key' => 'Key',
			'value' => 'Value',
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

		$criteria->compare('plugin_name',$this->plugin_name,true);
		$criteria->compare('key',$this->key,true);
		$criteria->compare('value',$this->value,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
