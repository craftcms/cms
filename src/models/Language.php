<?php
namespace Blocks;

/**
 *
 */
class Language extends Model
{
	protected $tableName = 'languages';

	protected $attributes = array(
		'language_code' => array('type' => AttributeType::LanguageCode, 'unique' => true)
	);
}
