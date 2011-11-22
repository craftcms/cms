<?php

/**
 * This is the model class for table "{{_contentblocks}}".
 *
 * The followings are the available columns in table '{{_contentblocks}}':
 * @property integer $id
 * @property integer $section_id
 * @property string $handle
 * @property string $label
 * @property string $type
 * @property string $instructions
 * @property integer $required
 * @property integer $display_order
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property ContentSections $section
 * @property ContentBlockSettings[] $contentblocksettings
 * @property ContentDrafts[] $blxContentdrafts
 * @property ContentVersions[] $blxContentversions
 */
class ContentBlocks extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return ContentBlocks the static model class
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
		return '{{_contentblocks}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('section_id, handle, label, type, display_order', 'required'),
			array('section_id, required, display_order, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('handle, type', 'length', 'max'=>150),
			array('label', 'length', 'max'=>500),
			array('uid', 'length', 'max'=>36),
			array('instructions', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, section_id, handle, label, type, instructions, required, display_order, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'section' => array(self::BELONGS_TO, 'ContentSections', 'section_id'),
			'contentBlockSettings' => array(self::HAS_MANY, 'ContentBlockSettings', 'block_id'),
			'contentDrafts' => array(self::MANY_MANY, 'ContentDrafts', '{{_contentdraftdata}}(block_id, draft_id)'),
			'contentVersions' => array(self::MANY_MANY, 'ContentVersions', '{{_contentversiondata}}(block_id, version_id)'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'section_id' => 'Section',
			'handle' => 'Handle',
			'label' => 'Label',
			'type' => 'Type',
			'instructions' => 'Instructions',
			'required' => 'Required',
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
		$criteria->compare('section_id',$this->section_id);
		$criteria->compare('handle',$this->handle,true);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('instructions',$this->instructions,true);
		$criteria->compare('required',$this->required);
		$criteria->compare('display_order',$this->display_order);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
