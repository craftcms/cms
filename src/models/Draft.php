<?php
namespace Blocks;

/**
 *
 */
class Draft extends Model
{
	protected $tableName = 'drafts';

	protected $attributes = array(
		'language' => AttributeType::Language,
		'name'     => AttributeType::Name,
		'splid'    => AttributeType::Int
	);

	protected $belongsTo = array(
		'entry'  => array('model' => 'Entry', 'required' => true),
		'author' => array('model' => 'User', 'required' => true)
	);

	protected $hasMany = array(
		'content' => array('model' => 'DraftContent', 'foreignKey' => 'draft')
	);
}
