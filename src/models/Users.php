<?php

/**
 * This is the model class for table "{{_users}}".
 *
 * The followings are the available columns in table '{{_users}}':
 * @property integer $id
 * @property string $user_name
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $password
 * @property string $salt
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property ContentDrafts[] $contentdrafts
 * @property ContentPages[] $contentpages
 * @property UserBlocks[] $userblocks
 * @property UserGroups[] $usergroups
 */
class Users extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Users the static model class
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
		return '{{_users}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_name, email, first_name, last_name, password, salt', 'required'),
			array('date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('user_name, email', 'length', 'max'=>250),
			array('first_name, last_name', 'length', 'max'=>100),
			array('password, salt', 'length', 'max'=>128),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_name, email, first_name, last_name, password, salt, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'contentDrafts' => array(self::HAS_MANY, 'ContentDrafts', 'author_id'),
			'contentPages' => array(self::HAS_MANY, 'ContentPages', 'author_id'),
			'userBlocks' => array(self::HAS_MANY, 'UserBlocks', 'user_id'),
			'userGroups' => array(self::HAS_MANY, 'UserGroups', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_name' => 'User Name',
			'email' => 'Email',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'password' => 'Password',
			'salt' => 'Salt',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('first_name',$this->first_name,true);
		$criteria->compare('last_name',$this->last_name,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('salt',$this->salt,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
