<?php
namespace Blocks;

/**
 *
 */
class Info extends BaseModel
{
	protected $tableName = 'info';

	protected $attributes = array(
		'edition' => AttributeType::Edition,
		'version' => AttributeType::Version,
		'build'   => AttributeType::Build
	);
}
