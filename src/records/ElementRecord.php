<?php
namespace Craft;

/**
 * Element record class.
 *
 * @package craft.app.records
 */
class ElementRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'elements';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'type'     => array(AttributeType::ClassName, 'required' => true),
			'enabled'  => array(AttributeType::Bool, 'default' => true),
			'archived' => array(AttributeType::Bool, 'default' => false),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('type')),
			array('columns' => array('enabled')),
			array('columns' => array('archived')),
		);
	}
}
