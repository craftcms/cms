<?php
namespace Blocks;

/**
 *
 */
class Info extends Model
{
	protected $tableName = 'info';

	protected $attributes = array(
		'edition' => AttributeType::Edition,
		'version' => AttributeType::Version,
		'build'   => AttributeType::Build,
		'online'  => AttributeType::Boolean
	);
}
