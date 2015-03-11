<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use Craft;

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
		return Craft::t('app','Find and Replace');
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
		return Craft::$app->templates->renderMacro('_includes/forms', 'textField', [
			[
				'name'        => 'find',
				'placeholder' => Craft::t('app','Find'),
			]
		]) .
		Craft::$app->templates->renderMacro('_includes/forms', 'textField', [
			[
				'name'        => 'replace',
				'placeholder' => Craft::t('app','Replace'),
			]
		]);
	}

	/**
	 * @inheritDoc ToolInterface::performAction()
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function performAction($params = [])
	{
		if (!empty($params['find']) && !empty($params['replace']))
		{
			Craft::$app->tasks->createTask('FindAndReplace', null, [
				'find'    => $params['find'],
				'replace' => $params['replace']
			]);
		}
	}
}
