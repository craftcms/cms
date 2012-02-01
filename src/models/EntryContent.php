<?php
namespace Blocks;

/**
 *
 */
class EntryContent extends BaseContentModel
{
	protected $tableName = 'entrycontent';
	protected $model = 'Entry';
	protected $foreignKey = 'entry';
}
