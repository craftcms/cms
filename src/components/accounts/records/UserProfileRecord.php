<?php
namespace Blocks;

/**
 *
 */
class UserProfileRecord extends BaseEntityRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'userprofiles';
	}

	/**
	 * Returns the list of blocks associated with this content.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getBlocks()
	{
		return blx()->users->getAllBlocks();
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'unique' => true, 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
