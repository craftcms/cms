<?php
namespace Blocks;

/**
 *
 */
class Content extends Model
{
	protected $tableName = 'content';

	protected $attributes = array(
		'title' => array('type' => AttributeType::Varchar, 'maxLength' => 255)
	);
}
