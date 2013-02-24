<?php
namespace Craft;

/**
 * Section locale model class
 */
class SectionLocaleModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'        => AttributeType::Number,
			'sectionId' => AttributeType::Number,
			'locale'    => AttributeType::Locale,
			'urlFormat' => AttributeType::Bool,
		);
	}
}
