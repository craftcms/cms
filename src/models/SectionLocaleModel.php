<?php
namespace Craft;

/**
 * Section locale model class
 */
class SectionLocaleModel extends BaseModel
{
	public $urlFormatIsRequired = false;
	public $nestedUrlFormatIsRequired = false;

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
			'urlFormat'       => array(AttributeType::UrlFormat, 'label' => 'URL Format'),
			'nestedUrlFormat' => array(AttributeType::UrlFormat, 'label' => 'URL Format'),
		);
	}

	/**
	 * Returns this model's validation rules.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if ($this->urlFormatIsRequired)
		{
			$rules[] = array('urlFormat', 'required');
		}

		if ($this->nestedUrlFormatIsRequired)
		{
			$rules[] = array('nestedUrlFormat', 'required');
		}

		return $rules;
	}
}
