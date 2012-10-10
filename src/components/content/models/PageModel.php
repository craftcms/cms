<?php
namespace Blocks;

/**
 * Page model class
 *
 * Used for transporting page data throughout the system.
 */
class PageModel extends BaseBlockEntityModel
{
	/**
	 * Use the translated page title as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Blocks::t($this->title);
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes['id'] = AttributeType::Number;
		$attributes['title'] = AttributeType::String;
		$attributes['uri'] = AttributeType::String;
		$attributes['template'] = AttributeType::String;

		return $attributes;
	}

	/**
	 * Returns the page's URL.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return Blocks::getSiteUrl().'/'.$this->uri;
	}
}
