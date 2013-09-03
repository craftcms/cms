<?php
namespace Craft;

/**
 * Section locale model class
 */
class SectionLocaleModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'              => AttributeType::Number,
			'sectionId'       => AttributeType::Number,
			'locale'          => AttributeType::Locale,
			'urlFormat'       => AttributeType::String,
			'nestedUrlFormat' => AttributeType::String,
		);
	}
}
