<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Email message model class.
 *
 * @package craft.app.models
 */
class RebrandEmailModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'key'      => AttributeType::String,
			'locale'   => AttributeType::Locale,
			'subject'  => AttributeType::String,
			'body'     => AttributeType::String,
			'htmlBody' => AttributeType::String,
		);
	}
}
