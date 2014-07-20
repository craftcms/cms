<?php
namespace Craft;

/**
 * Entry type model class.
 *
 * @package craft.app.models
 */
class EntryTypeModel extends BaseModel
{
	/**
	 * Use the handle as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->handle;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'sectionId'     => AttributeType::Number,
			'fieldLayoutId' => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'hasTitleField' => array(AttributeType::Bool, 'default' => true),
			'titleLabel'    => array(AttributeType::String, 'default' => Craft::t('Title')),
			'titleFormat'   => AttributeType::String,
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(ElementType::Entry),
		);
	}

	/**
	 * Returns the entry type's CP edit URL.
	 *
	 * @return string
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('settings/sections/'.$this->sectionId.'/entrytypes/'.$this->id);
	}
}
