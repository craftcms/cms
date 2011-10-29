<?php

/**
 * This is the model class for table "{{_contentpagetitles}}".
 *
 * The followings are the available columns in table '{{_contentpagetitles}}':
 * @property integer $page_id
 * @property string $language_code
 * @property string $title
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property ContentPages $page
 */
class ContentPageTitles extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return ContentPageTitles the static model class
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
		return '{{_contentpagetitles}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('language_code, title', 'required'),
			array('date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('language_code', 'length', 'max'=>16),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('page_id, language_code, title, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'page' => array(self::BELONGS_TO, 'ContentPages', 'page_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'page_id' => 'Page',
			'language_code' => 'Language Code',
			'title' => 'Title',
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

		$criteria->compare('page_id',$this->page_id);
		$criteria->compare('language_code',$this->language_code,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
