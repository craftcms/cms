<?php

/**
 * This is the model class for table "{{_usergroups}}".
 *
 * The followings are the available columns in table '{{_usergroups}}':
 * @property integer $id
 * @property integer $group_id
 * @property integer $user_id
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 * @property integer $site_id
 *
 * The followings are the available model relations:
 * @property UserGroupPermissions[] $usergrouppermissions
 * @property Sites $site
 * @property Groups $group
 * @property Users $user
 */
class UserGroups extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return UserGroups the static model class
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
		return '{{_usergroups}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('group_id, user_id, site_id', 'required'),
			array('group_id, user_id, date_created, date_updated, site_id', 'numerical', 'integerOnly'=>true),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, group_id, user_id, date_created, date_updated, uid, site_id', 'safe', 'on'=>'search'),
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
			'usergrouppermissions' => array(self::HAS_MANY, 'UserGroupPermissions', 'user_group_id'),
			'site' => array(self::BELONGS_TO, 'Sites', 'site_id'),
			'group' => array(self::BELONGS_TO, 'Groups', 'group_id'),
			'user' => array(self::BELONGS_TO, 'Users', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'group_id' => 'Group',
			'user_id' => 'User',
			'date_created' => 'Date Created',
			'date_updated' => 'Date Updated',
			'uid' => 'Uid',
			'site_id' => 'Site',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('group_id',$this->group_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);
		$criteria->compare('site_id',$this->site_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
