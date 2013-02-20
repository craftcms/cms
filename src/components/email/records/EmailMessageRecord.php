<?php
namespace Blocks;

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
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'key'      => array(AttributeType::String, 'required' => true, 'maxLength' => 150, 'column' => ColumnType::Char),
			'locale'   => array(AttributeType::Locale, 'required' => true),
			'subject'  => array(AttributeType::String, 'required' => true, 'maxLength' => 1000),
			'body'     => array(AttributeType::String, 'required' => true, 'column' => ColumnType::Text),
			'htmlBody' => array(AttributeType::String, 'column' => ColumnType::Text),
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
