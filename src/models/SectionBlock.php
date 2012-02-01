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
}
