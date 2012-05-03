<?php
namespace Blocks;

/**
 *
 */
class Info extends Model
{
	protected $tableName = 'info';

	protected $attributes = array(
		'version'       => AttributeType::Version,
		'build'         => AttributeType::Build,
		'release_date'  => array(AttributeType::Int, 'required' => true),
		'on'            => AttributeType::Boolean,
	);
}
