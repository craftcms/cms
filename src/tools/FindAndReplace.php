<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use craft\app\Craft;

/**
 * Find and Replace tool
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FindAndReplace extends BaseTool
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Find and Replace');
	}

	/**
	 * @inheritDoc ToolInterface::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'wand';
	}

	/**
	 * @inheritDoc ToolInterface::getOptionsHtml()
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'name'        => 'find',
				'placeholder' => Craft::t('Find'),
			)
		)) .
		craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'name'        => 'replace',
				'placeholder' => Craft::t('Replace'),
			)
		));
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
		if (!empty($params['find']) && !empty($params['replace']))
		{
			craft()->tasks->createTask('FindAndReplace', null, array(
				'find'    => $params['find'],
				'replace' => $params['replace']
			));
		}
	}
}
