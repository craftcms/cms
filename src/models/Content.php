<?php
namespace Blocks;

/**
 *
 */
class Content extends BaseModel
{
	protected $tableName = 'content';

	protected $attributes = array(
		'language_code' => AttributeType::LanguageCode
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
