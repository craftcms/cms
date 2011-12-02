<?php

/**
 * This is the model class for table "{{entrydrafts}}".
 *
 * The followings are the available columns in table '{{entrydrafts}}':
 * @property integer $id
 * @property integer $entry_id
 * @property integer $author_id
 * @property string $label
 * @property integer $date_created
 * @property integer $date_updated
 * @property string $uid
 *
 * The followings are the available model relations:
 * @property EntryBlocks[] $entryBlocks
 * @property Entries $entry
 * @property Users $author
 */
class EntryDrafts extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return EntryDrafts the static model class
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
		return '{{entrydrafts}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('entry_id, author_id, label', 'required'),
			array('entry_id, author_id, date_created, date_updated', 'numerical', 'integerOnly'=>true),
			array('uid', 'length', 'max'=>36),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, entry_id, author_id, label, date_created, date_updated, uid', 'safe', 'on'=>'search'),
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
			'entryBlocks' => array(self::MANY_MANY, 'EntryBlocks', '{{entrydraftdata}}(draft_id, block_id)'),
			'entry' => array(self::BELONGS_TO, 'Entries', 'entry_id'),
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
			'entry_id' => 'Entry',
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
		$criteria->compare('entry_id',$this->entry_id);
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
