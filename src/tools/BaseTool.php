<?php
namespace Craft;

/**
 * Tool base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tools
 * @since     1.0
 */
abstract class BaseTool extends BaseComponentType implements ITool
{
	// Properties
	// =========================================================================

	/**
	 * The type of component this is.
	 *
	 * @var string
	 */
	protected $componentType = 'Tool';

	// Public Methods
	// =========================================================================

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
	 *
	 * @return array
	 */
	public function performAction($params = array())
	{
		return array('complete' => true);
	}
}
