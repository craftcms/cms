<?php
namespace Blocks;

/**
 *
 */
class DraftContent extends Model
{
	protected $tableName = 'draftcontent';

	protected $attributes = array(
		'value' => AttributeType::Text
	);

	protected $belongsTo = array(
		'draft' => array('model' => 'Draft', 'required' => true),
		'block' => array('model' => 'Block')
	);
}
