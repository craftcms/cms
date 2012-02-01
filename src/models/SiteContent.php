<?php
namespace Blocks;

/**
 *
 */
class SiteContent extends BaseContentModel
{
	protected $tableName = 'sitecontent';
	protected $model = 'Site';
	protected $foreignKey = 'site';
}
