<?php
namespace Blocks;

/**
 *
 */
class SectionBlock extends BaseBlockModel
{
	public function getTableName()
	{
		return 'sectionblocks';
	}

	protected function getRelations()
	{
		return array(
			'section' => array(static::BELONGS_TO, 'Section'),
		);
	}
}
