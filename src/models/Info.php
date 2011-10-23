<?php

/**
 * This is the model class for table "{{_info}}".
 *
 * The followings are the available columns in table '{{_info}}':
 * @property integer $id
 * @property string $version
 * @property string $build_number
 * @property string $edition
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 */
class Info extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Info the static model class
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
		return '{{_info}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('version, build_number, edition', 'required'),
			array('date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('version', 'length', 'max'=>15),
			array('build_number', 'length', 'max'=>10),
			array('edition', 'length', 'max'=>12),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, version, build_number, edition, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'id' => 'ID',
			'version' => 'Version',
			'build_number' => 'Build Number',
			'edition' => 'Edition',
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
		$criteria->compare('version',$this->version,true);
		$criteria->compare('build_number',$this->build_number,true);
		$criteria->compare('edition',$this->edition,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}