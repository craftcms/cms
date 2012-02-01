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

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
