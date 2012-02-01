<?php
namespace Blocks;

/**
 *
 */
class SiteBlock extends BaseBlocksModel
{
	protected $tableName = 'siteblocks';
	protected $model = 'Site';
	protected $foreignKey = 'site';
}
