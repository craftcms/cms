<?php
namespace Blocks;

/**
 *
 */
class EntryVersionContent extends Model
{
	protected $tableName = 'entryversioncontent';

	protected $attributes = array(
		'title' => AttributeType::Boolean,
		'value' => AttributeType::Text
	);

	protected $belongsTo = array(
		'version' => array('model' => 'EntryVersion', 'required' => true),
		'block'   => array('model' => 'Block')
	);
}
