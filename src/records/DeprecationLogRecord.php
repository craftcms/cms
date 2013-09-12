<?php
namespace Craft;

class DeprecationLogRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'deprecationlog';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'key'               => array(AttributeType::String, 'required' => true),
			'fingerprint'       => array(AttributeType::String, 'required' => true),
			'message'           => array(AttributeType::String, 'required' => true),
			'deprecatedSince'   => array(AttributeType::String, 'maxLength' => 25, 'required' => true),
			'stackTrace'        => array(AttributeType::String, 'column' => ColumnType::Text, 'required' => true),
			'file'              => array(AttributeType::String, 'required' => true),
			'line'              => array(AttributeType::Number, 'required' => true),
			'method'            => array(AttributeType::String, 'maxLength' => 150),
			'class'             => array(AttributeType::ClassName),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('key', 'fingerprint'), 'unique' => true),
		);
	}
}
