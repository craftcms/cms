<?php

/**
 * This is the model class for table "{{userwidgets}}".
 *
 * The followings are the available columns in table '{{userwidgets}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $class
 * @property string $sort_order
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property Users $user
 * @property UserWidgetSettings[] $userWidgetSettings
 */
class UserWidgetsAR extends BlocksActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return UserWidgets the static model class
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
		return '{{userwidgets}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, class, sort_order', 'required'),
			array('user_id, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('class', 'length', 'max'=>150),
			array('sort_order', 'length', 'max'=>11),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, class, sort_order, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'userWidgetSettings' => array(self::HAS_MANY, 'UserWidgetSettings', 'widget_id'),
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
			'class' => 'Class',
			'sort_order' => 'Sort Order',
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
		$criteria->compare('class',$this->class,true);
		$criteria->compare('sort_order',$this->sort_order,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
