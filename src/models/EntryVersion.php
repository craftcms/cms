<?php
namespace Blocks;

/**
 *
 */
class EntryVersion extends Model
{
	protected $tableName = 'entryversions';

	protected $attributes = array(
		'language' => AttributeType::Language,
		'draft'    => array(AttributeType::Boolean, 'indexed' => true),
		'num'      => array('type' => AttributeType::Int, 'unsigned' => true, 'indexed' => true),
		'name'     => AttributeType::Name,
		'notes'    => AttributeType::Text
	);

	protected $belongsTo = array(
		'entry'  => array('model' => 'Entry', 'required' => true),
		'author' => array('model' => 'User', 'required' => true)
	);

	protected $hasMany = array(
		'content' => array('model' => 'EntryVersionContent', 'foreignKey' => 'version')
	);
}
