<?php
namespace Blocks;

/**
 * Entry locale data record class
 */
class EntryLocalizationRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entries_i18n';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'locale' => array(AttributeType::Locale, 'required' => true),
			'title'  => array(AttributeType::String, 'required' => true),
			'uri'    => AttributeType::String,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'locale' => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('entryId', 'locale'), 'unique' => true),
			array('columns' => array('uri', 'locale'), 'unique' => true),
			array('columns' => array('title')),
		);
	}
}
