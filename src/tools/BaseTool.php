<?php
namespace craft\app\tools;

use craft\app\components\BaseComponentType;

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
abstract class BaseTool extends BaseComponentType implements ToolInterface
{
	// Properties
	// =========================================================================

	/**
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'Tool';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ToolInterface::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'tool';
	}

	/**
	 * @inheritDoc ToolInterface::getOptionsHtml()
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return '';
	}

	/**
	 * @inheritDoc ToolInterface::getButtonLabel()
	 *
	 * @return string
	 */
	public function getButtonLabel()
	{
		return Craft::t('Go!');
	}

	/**
	 * @inheritDoc ToolInterface::performAction()
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
