<?php
namespace Blocks;

/**
 *
 */
class DraftContent extends Model
{
	protected $tableName = 'draftcontent';

	protected $attributes = array(
		'title' => array(AttributeType::Boolean),
		'value' => AttributeType::Text
	);

	protected $belongsTo = array(
		'draft' => array('model' => 'Draft', 'required' => true),
		'block' => array('model' => 'Block')
	);

	protected $indexes = array(
		array('columns' => array('title'))
	);
}
