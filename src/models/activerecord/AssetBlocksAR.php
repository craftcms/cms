<?php

/**
 * This is the model class for table "{{assetblocks}}".
 *
 * The followings are the available columns in table '{{assetblocks}}':
 * @property integer $id
 * @property integer $asset_id
 * @property string $type
 * @property string $handle
 * @property string $label
 * @property string $sort_order
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property Assets $asset
 * @property AssetBlockSettings[] $assetBlockSettings
 */
class AssetBlocksAR extends BlocksActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return AssetBlocks the static model class
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
		return '{{assetblocks}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, handle, label, sort_order', 'required'),
			array('asset_id, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('type, handle', 'length', 'max'=>150),
			array('label', 'length', 'max'=>500),
			array('sort_order', 'length', 'max'=>11),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, asset_id, type, handle, label, sort_order, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'asset' => array(self::BELONGS_TO, 'Assets', 'asset_id'),
			'assetBlockSettings' => array(self::HAS_MANY, 'AssetBlockSettings', 'asset_block_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'asset_id' => 'Asset',
			'type' => 'Type',
			'handle' => 'Handle',
			'label' => 'Label',
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
		$criteria->compare('asset_id',$this->asset_id);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('handle',$this->handle,true);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('sort_order',$this->sort_order,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
