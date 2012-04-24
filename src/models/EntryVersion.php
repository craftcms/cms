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
		'draft'    => AttributeType::Boolean,
		'num'      => array('type' => AttributeType::Int, 'unsigned' => true, 'required' => true),
		'name'     => AttributeType::Name,
		'notes'    => AttributeType::TinyText,
		'changes'  => AttributeType::MediumText
	);

	protected $belongsTo = array(
		'entry'  => array('model' => 'Entry', 'required' => true),
		'author' => array('model' => 'User', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('num','draft','entry_id'), 'unique' => true)
	);

	public function name()
	{
		if ($this->draft)
			return 'Draft '.$this->num;
		else
			return 'Version '.$this->num;
	}
}
