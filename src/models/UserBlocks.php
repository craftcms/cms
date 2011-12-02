<?php

/**
 * This is the model class for table "{{userblocks}}".
 *
 * The followings are the available columns in table '{{userblocks}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $type
 * @property string $handle
 * @property string $label
 * @property string $instructions
 * @property integer $display_order
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property Users $user
 * @property UserBlockSettings[] $userBlockSettings
 */
class UserBlocks extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return UserBlocks the static model class
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
		return '{{userblocks}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, type, handle, label, display_order', 'required'),
			array('user_id, display_order, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('type, handle', 'length', 'max'=>150),
			array('label', 'length', 'max'=>500),
			array('uid', 'length', 'max'=>36),
			array('instructions', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, type, handle, label, instructions, display_order, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'user' => array(self::BELONGS_TO, 'Users', 'user_id'),
			'userBlockSettings' => array(self::HAS_MANY, 'UserBlockSettings', 'block_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'type' => 'Type',
			'handle' => 'Handle',
			'label' => 'Label',
			'instructions' => 'Instructions',
			'display_order' => 'Display Order',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('handle',$this->handle,true);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('instructions',$this->instructions,true);
		$criteria->compare('display_order',$this->display_order);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
