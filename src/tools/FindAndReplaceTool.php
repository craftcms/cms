<?php
namespace Craft;

/**
 * Find and Replace tool
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.tools
 * @since     2.0
 */
class FindAndReplaceTool extends BaseTool
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Find and Replace');
	}

	/**
	 * @inheritDoc ITool::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'wand';
	}

	/**
	 * @inheritDoc ITool::getOptionsHtml()
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
	 * @inheritDoc ITool::performAction()
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
