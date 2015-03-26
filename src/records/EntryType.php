<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Class EntryType record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryType extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%entrytypes}}';
	}

	/**
	 * Returns the entry type’s section.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSection()
	{
		return $this->hasOne(Section::className(), ['id' => 'sectionId']);
	}

	/**
	 * Returns the entry type’s fieldLayout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFieldLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'fieldLayoutId']);
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['name', 'sectionId'], 'unique' => true],
			['columns' => ['handle', 'sectionId'], 'unique' => true],
		];
	}

	/**
	 * @inheritDoc ActiveRecord::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if (!$this->hasTitleField)
		{
			$rules[] = ['titleFormat', 'required'];
		}

		return $rules;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc ActiveRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'name'          => [AttributeType::Name, 'required' => true],
			'handle'        => [AttributeType::Handle, 'required' => true],
			'hasTitleField' => [AttributeType::Bool, 'required' => true, 'default' => true],
			'titleLabel'    => [AttributeType::String, 'default' => 'Title'],
			'titleFormat'   => AttributeType::String,
			'sortOrder'     => AttributeType::SortOrder,
		];
	}
}
