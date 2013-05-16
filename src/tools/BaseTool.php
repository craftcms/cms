<?php
namespace Craft;

/**
 * Tool base class
 */
abstract class BaseTool extends BaseComponentType implements ITool
{
	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'Tool';

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return '';
	}
}
