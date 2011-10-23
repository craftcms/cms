<?php

/**
 * This is the model class for table "{{_assets}}".
 *
 * The followings are the available columns in table '{{_assets}}':
 * @property integer $id
 * @property integer $upload_folder_id
 * @property string $path
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property AssetBlocks[] $assetblocks
 * @property UploadFolders $uploadFolder
 */
class Assets extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Assets the static model class
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
		return '{{_assets}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('upload_folder_id, path', 'required'),
			array('upload_folder_id, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('path', 'length', 'max'=>500),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, upload_folder_id, path, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'assetblocks' => array(self::HAS_MANY, 'Assetblocks', 'asset_id'),
			'uploadFolder' => array(self::BELONGS_TO, 'Uploadfolders', 'upload_folder_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'upload_folder_id' => 'Upload Folder',
			'path' => 'Path',
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
		$criteria->compare('upload_folder_id',$this->upload_folder_id);
		$criteria->compare('path',$this->path,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
