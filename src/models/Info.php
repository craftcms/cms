<?php
namespace Blocks;

/**
 *
 */
class Info extends Model
{
	protected $tableName = 'info';

	protected $attributes = array(
		'edition'       => AttributeType::Edition,
		'version'       => AttributeType::Version,
		'build'         => AttributeType::Build,
		'on'            => AttributeType::Boolean,
		'release_date'  => array(AttributeType::Int, 'required' => true)
	);
}
