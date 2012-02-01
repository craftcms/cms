<?php
namespace Blocks;

/**
 *
 */
class Language extends BaseModel
{
	protected $tableName = 'languages';

	protected $attributes = array(
		'language_code' => array('type' => AttributeType::LanguageCode, 'unique' => true)
	);
}
