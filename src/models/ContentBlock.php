<?php
namespace Blocks;

/**
 *
 */
class ContentBlock extends BaseModel
{
	protected $tableName = 'contentblocks';

	/**
	 * @return array
	 */
	protected $attributes = array(
		'name'         => AttributeType::Name,
		'handle'       => AttributeType::Handle,
		'class'        => AttributeType::ClassName,
		'instructions' => AttributeType::Text
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Site', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('site_id','handle'), 'unique' => true)
	);
}
