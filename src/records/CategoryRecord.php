<?php
namespace Craft;

/**
 * Category record
 */
class CategoryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'categories';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'group'   => array(static::BELONGS_TO, 'CategoryGroupRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
