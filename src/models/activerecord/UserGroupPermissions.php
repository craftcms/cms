<?php

/**
 * This is the model class for table "{{usergrouppermissions}}".
 *
 * The followings are the available columns in table '{{usergrouppermissions}}':
 * @property integer $user_group_id
 * @property string $key
 * @property integer $value
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property UserGroups $userGroup
 */
class UserGroupPermissions extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return UserGroupPermissions the static model class
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
		return '{{usergrouppermissions}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_group_id, key', 'required'),
			array('user_group_id, value, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('key', 'length', 'max'=>100),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('user_group_id, key, value, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'userGroup' => array(self::BELONGS_TO, 'UserGroups', 'user_group_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'user_group_id' => 'User Group',
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

		$criteria->compare('user_group_id',$this->user_group_id);
		$criteria->compare('key',$this->key,true);
		$criteria->compare('value',$this->value);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
