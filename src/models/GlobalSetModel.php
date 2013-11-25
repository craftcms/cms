<?php
namespace Craft;

/**
 * GlobalSet model class
 *
 * Used for transporting page data throughout the system.
 */
class GlobalSetModel extends BaseElementModel
{
	protected $elementType = ElementType::GlobalSet;

	/**
	 * Use the global set's name as its string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->name;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'name'          => AttributeType::Name,
			'handle'        => AttributeType::Handle,
			'fieldLayoutId' => AttributeType::Number,
		));
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(ElementType::GlobalSet),
		);
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('globals/'.$this->handle);
	}
}
