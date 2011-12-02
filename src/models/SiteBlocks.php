<?php

/**
 * This is the model class for table "{{siteblocks}}".
 *
 * The followings are the available columns in table '{{siteblocks}}':
 * @property integer $id
 * @property integer $site_id
 * @property string $handle
 * @property string $label
 * @property string $type
 * @property string $instructions
 * @property integer $display_order
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property Sites $site
 * @property SiteBlockSettings[] $siteBlockSettings
 */
class SiteBlocks extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return SiteBlocks the static model class
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
		return '{{siteblocks}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('site_id, handle, label, type, display_order', 'required'),
			array('site_id, display_order, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('handle, type', 'length', 'max'=>150),
			array('label', 'length', 'max'=>500),
			array('uid', 'length', 'max'=>36),
			array('instructions', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, site_id, handle, label, type, instructions, display_order, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'site' => array(self::BELONGS_TO, 'Sites', 'site_id'),
			'siteBlockSettings' => array(self::HAS_MANY, 'SiteBlockSettings', 'block_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'site_id' => 'Site',
			'handle' => 'Handle',
			'label' => 'Label',
			'type' => 'Type',
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
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('handle',$this->handle,true);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('type',$this->type,true);
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
