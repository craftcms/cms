<?php
namespace Craft;

craft()->requirePackage(CraftPackage::Rebrand);

/**
 *
 */
class EmailMessageRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'emailmessages';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'key'      => array(AttributeType::String, 'required' => true, 'maxLength' => 150, 'column' => ColumnType::Char),
			'locale'   => array(AttributeType::Locale, 'required' => true),
			'subject'  => array(AttributeType::String, 'required' => true, 'maxLength' => 1000),
			'body'     => array(AttributeType::String, 'required' => true, 'column' => ColumnType::Text),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'locale'  => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('key', 'locale'), 'unique' => true),
		);
	}
}
