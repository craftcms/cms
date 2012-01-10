<?php

/**
 * This is the model class for table "{{sites}}".
 *
 * The followings are the available columns in table '{{sites}}':
 * @property integer $id
 * @property string $handle
 * @property string $label
 * @property string $url
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property Routes[] $routes
 * @property Sections[] $sections
 * @property SiteBlocks[] $siteBlocks
 * @property SiteSettings[] $siteSettings
 * @property UploadFolders[] $uploadFolders
 * @property UserGroups[] $userGroups
 */
class SitesAR extends BlocksActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Sites the static model class
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
		return '{{sites}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('handle, label, url', 'required'),
			array('date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('handle', 'length', 'max'=>150),
			array('label', 'length', 'max'=>500),
			array('url', 'length', 'max'=>250),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, handle, label, url, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'routes' => array(self::HAS_MANY, 'Routes', 'site_id'),
			'sections' => array(self::HAS_MANY, 'Sections', 'site_id'),
			'siteBlocks' => array(self::HAS_MANY, 'SiteBlocks', 'site_id'),
			'siteSettings' => array(self::HAS_MANY, 'SiteSettings', 'site_id'),
			'uploadFolders' => array(self::HAS_MANY, 'UploadFolders', 'site_id'),
			'userGroups' => array(self::HAS_MANY, 'UserGroups', 'site_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'handle' => 'Handle',
			'label' => 'Label',
			'url' => 'Url',
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
		$criteria->compare('handle',$this->handle,true);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
