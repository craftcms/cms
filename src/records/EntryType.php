<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;
use craft\app\enums\AttributeType;

/**
 * Class EntryType record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryType extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'entrytypes';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'section'     => [static::BELONGS_TO, 'Section', 'required' => true, 'onDelete' => static::CASCADE],
			'fieldLayout' => [static::BELONGS_TO, 'FieldLayout', 'onDelete' => static::SET_NULL],
		];
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
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
	 * @inheritDoc BaseRecord::rules()
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
	 * @inheritDoc BaseRecord::defineAttributes()
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
