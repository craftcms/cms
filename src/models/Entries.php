<?php

/**
 * This is the model class for table "{{entries}}".
 *
 * The followings are the available columns in table '{{entries}}':
 * @property integer $id
 * @property integer $parent_id
 * @property integer $section_id
 * @property integer $author_id
 * @property string $slug
 * @property string $full_uri
 * @property integer $post_date
 * @property integer $expiry_date
 * @property integer $sort_order
 * @property integer $enabled
 * @property integer $archived
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property Sections $section
 * @property Entries $parent
 * @property Entries[] $entries
 * @property Users $author
 * @property EntryDrafts[] $entryDrafts
 * @property EntryTitles[] $entryTitles
 * @property EntryVersions[] $entryVersions
 */
class Entries extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Entries the static model class
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
		return '{{entries}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('section_id, author_id', 'required'),
			array('parent_id, section_id, author_id, post_date, expiry_date, sort_order, enabled, archived, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('slug', 'length', 'max'=>250),
			array('full_uri', 'length', 'max'=>1000),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, parent_id, section_id, author_id, slug, full_uri, post_date, expiry_date, sort_order, enabled, archived, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'section' => array(self::BELONGS_TO, 'Sections', 'section_id'),
			'parent' => array(self::BELONGS_TO, 'Entries', 'parent_id'),
			'entries' => array(self::HAS_MANY, 'Entries', 'parent_id'),
			'author' => array(self::BELONGS_TO, 'Users', 'author_id'),
			'entryDrafts' => array(self::HAS_MANY, 'EntryDrafts', 'entry_id'),
			'entryTitles' => array(self::HAS_MANY, 'EntryTitles', 'entry_id'),
			'entryVersions' => array(self::HAS_MANY, 'EntryVersions', 'entry_id'),
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
			'section_id' => 'Section',
			'author_id' => 'Author',
			'slug' => 'Slug',
			'full_uri' => 'Full Uri',
			'post_date' => 'Post Date',
			'expiry_date' => 'Expiry Date',
			'sort_order' => 'Sort Order',
			'enabled' => 'Enabled',
			'archived' => 'Archived',
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
		$criteria->compare('section_id',$this->section_id);
		$criteria->compare('author_id',$this->author_id);
		$criteria->compare('slug',$this->slug,true);
		$criteria->compare('full_uri',$this->full_uri,true);
		$criteria->compare('post_date',$this->post_date);
		$criteria->compare('expiry_date',$this->expiry_date);
		$criteria->compare('sort_order',$this->sort_order);
		$criteria->compare('enabled',$this->enabled);
		$criteria->compare('archived',$this->archived);
		$criteria->compare('date_created',$this->date_created);
		$criteria->compare('date_updated',$this->date_updated);
		$criteria->compare('uid',$this->uid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
