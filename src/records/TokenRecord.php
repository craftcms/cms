<?php
namespace Craft;

/**
 * Token record
 */
class TokenRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'tokens';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'token'      => array(AttributeType::String, 'column' => ColumnType::Char, 'length' => 32, 'required' => true),
			'route'      => array(AttributeType::Mixed),
			'usageLimit' => array(AttributeType::Number, 'min' => 0, 'max' => 255),
			'usageCount' => array(AttributeType::Number, 'min' => 0, 'max' => 255),
			'expiryDate' => array(AttributeType::DateTime, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('token'), 'unique' => true),
			array('columns' => array('expiryDate')),
		);
	}
}
