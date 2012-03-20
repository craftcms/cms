<?php
namespace Blocks;

/**
 *
 */
class EntryTitle extends Model
{
	protected $tableName = 'entrytitles';

	protected $attributes = array(
		'language' => AttributeType::Language,
		'title'    => array('type' => AttributeType::Varchar, 'maxLength' => 255)
	);

	protected $belongsTo = array(
		'entry' => array('model' => 'Entry', 'required' => true)
	);

	public function __toString()
	{
		return $this->title;
	}
}
