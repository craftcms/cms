<?php
namespace Blocks;

/**
 *
 */
class Route extends BaseModel
{
	protected $tableName = 'routes';

	protected $attributes = array(
		'url_parts'   => array('type' => AttributeType::Varchar, 'required' => true),
		'url_pattern' => array('type' => AttributeType::Varchar, 'required' => true),
		'template'    => array('type' => AttributeType::Varchar, 'required' => true),
		'sort_order'  => array('type' => AttributeType::Int, 'required' => true),
	);

	protected $indexes = array(
		array('columns' => array('url_pattern'), 'unique' => true),
	);

	public function scopes()
	{
		return array(
			'ordered' => array(
				'order' => 'sort_order ASC'
			)
		);
	}
}
