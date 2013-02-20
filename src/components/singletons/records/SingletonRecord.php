<?php
namespace Blocks;

/**
 *
 */
class SingletonRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'singletons';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'          => AttributeType::Name,
			'template'      => AttributeType::Template,
			'fieldLayoutId' => AttributeType::Number,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entry'       => array(static::BELONGS_TO, 'EntryRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
			'locales'     => array(static::HAS_MANY, 'SingletonLocaleRecord', 'singletonId'),
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
		);
	}
}
