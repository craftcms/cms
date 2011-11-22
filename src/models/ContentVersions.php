<?php

/**
 * This is the model class for table "{{_contentversions}}".
 *
 * The followings are the available columns in table '{{_contentversions}}':
 * @property integer $id
 * @property integer $page_id
 * @property integer $num
 * @property string $label
 * @property integer $is_live
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property ContentBlocks[] $contentblocks
 * @property ContentPages $page
 */
class ContentVersions extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return ContentVersions the static model class
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
		return '{{_contentversions}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('page_id, num, label, is_live', 'required'),
			array('page_id, num, is_live, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, page_id, num, label, is_live, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'contentBlocks' => array(self::MANY_MANY, 'ContentBlocks', '{{_contentversiondata}}(version_id, block_id)'),
			'page' => array(self::BELONGS_TO, 'ContentPages', 'page_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'page_id' => 'Page',
			'num' => 'Num',
			'label' => 'Label',
			'is_live' => 'Is Live',
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
		$criteria->compare('page_id',$this->page_id);
		$criteria->compare('num',$this->num);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('is_live',$this->is_live);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
