<?php

/**
 * This is the model class for table "{{migrations}}".
 *
 * The followings are the available columns in table '{{migrations}}':
 * @property string $version
 * @property integer $apply_time
 */
class MigrationsAR extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Migrations the static model class
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
		return '{{migrations}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('version', 'required'),
			array('apply_time', 'numerical', 'integerOnly'=>true),
			array('version', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('version, apply_time', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'version' => 'Version',
			'apply_time' => 'Apply Time',
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

		$criteria->compare('version',$this->version,true);
		$criteria->compare('apply_time',$this->apply_time);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
