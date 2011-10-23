<?php

/**
 * This is the model class for table "{{_contentdrafts}}".
 *
 * The followings are the available columns in table '{{_contentdrafts}}':
 * @property integer $id
 * @property integer $page_id
 * @property integer $author_id
 * @property string $label
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property ContentBlocks[] $blxContentblocks
 * @property ContentPages $page
 * @property Users $author
 */
class ContentDrafts extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return ContentDrafts the static model class
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
		return '{{_contentdrafts}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('page_id, author_id, label', 'required'),
			array('page_id, author_id, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, page_id, author_id, label, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'blxContentblocks' => array(self::MANY_MANY, 'Contentblocks', '{{_contentdraftdata}}(draft_id, block_id)'),
			'page' => array(self::BELONGS_TO, 'Contentpages', 'page_id'),
			'author' => array(self::BELONGS_TO, 'Users', 'author_id'),
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
			'author_id' => 'Author',
			'label' => 'Label',
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
		$criteria->compare('author_id',$this->author_id);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
