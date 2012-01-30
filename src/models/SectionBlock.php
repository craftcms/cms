<?php
namespace Blocks;

/**
 *
 */
class SectionBlock extends BaseBlocksModel
{
	protected $tableName = 'sectionblocks';
	protected $model = 'Section';
	protected $foreignKey = 'section';

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
