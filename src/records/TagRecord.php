<?php
namespace Craft;

/**
 *
 */
class TagRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'tags';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'   => AttributeType::String,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'set'     => array(static::BELONGS_TO, 'TagSetRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('setId', 'name'), 'unique' => true),
		);
	}
}
