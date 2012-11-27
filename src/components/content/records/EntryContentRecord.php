<?php
namespace Blocks;

/**
 *
 */
class EntryContentRecord extends BaseEntityRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entrycontent';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes['language'] = array(AttributeType::Language, 'required' => true);
		$attributes = array_merge($attributes, parent::defineAttributes());
		return $attributes;
	}

	/**
	 * Returns the list of blocks associated with this content.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getBlocks()
	{
		return blx()->entries->getAllBlocks();
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
