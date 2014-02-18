<?php
namespace Craft;

/**
 * Stores the locales
 */
class LocaleRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'locales';
	}

	/**
	 * @return string
	 */
	public function primaryKey()
	{
		return 'locale';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'locale'    => array(AttributeType::Locale, 'required' => true, 'primaryKey' => true),
			'sortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sortOrder')),
		);
	}
}
