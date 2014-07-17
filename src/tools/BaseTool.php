<?php
namespace Craft;

/**
 * Tool base class.
 *
 * @abstract
 * @implements ITool
 * @package craft.app.tools
 */
abstract class BaseTool extends BaseComponentType implements ITool
{
	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'Tool';

	/**
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'tool';
	}

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return '';
	}

	/**
	 * Returns the tool's button label.
	 *
	 * @return string
	 */
	public function getButtonLabel()
	{
		return Craft::t('Go!');
	}

	/**
	 * Performs the tool's action.
	 *
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array())
	{
		return array('complete' => true);
	}
}
