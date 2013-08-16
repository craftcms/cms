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

	private $_fieldLayout;

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
			'fieldLayout' => new FieldLayoutBehavior(),
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
