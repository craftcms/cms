<?php

/**
 * This is the model class for table "{{_contentsections}}".
 *
 * The followings are the available columns in table '{{_contentsections}}':
 * @property integer $id
 * @property integer $parent_id
 * @property integer $site_id
 * @property string $handle
 * @property string $label
 * @property string $url_format
 * @property integer $max_pages
 * @property string $template
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property ContentBlocks[] $contentblocks
 * @property ContentPages[] $contentpages
 * @property ContentSections $parent
 * @property ContentSections[] $contentsections
 * @property Sites $site
 */
class ContentSections extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return ContentSections the static model class
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
		return '{{_contentsections}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('site_id, handle, label', 'required'),
			array('parent_id, site_id, max_pages, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('handle', 'length', 'max'=>150),
			array('label, template', 'length', 'max'=>500),
			array('url_format', 'length', 'max'=>250),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, parent_id, site_id, handle, label, url_format, max_pages, template, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'contentblocks' => array(self::HAS_MANY, 'Contentblocks', 'section_id'),
			'contentpages' => array(self::HAS_MANY, 'Contentpages', 'section_id'),
			'parent' => array(self::BELONGS_TO, 'Contentsections', 'parent_id'),
			'contentsections' => array(self::HAS_MANY, 'Contentsections', 'parent_id'),
			'site' => array(self::BELONGS_TO, 'Sites', 'site_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'parent_id' => 'Parent',
			'site_id' => 'Site',
			'handle' => 'Handle',
			'label' => 'Label',
			'url_format' => 'Url Format',
			'max_pages' => 'Max Pages',
			'template' => 'Template',
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
		$criteria->compare('parent_id',$this->parent_id);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('handle',$this->handle,true);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('url_format',$this->url_format,true);
		$criteria->compare('max_pages',$this->max_pages);
		$criteria->compare('template',$this->template,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
