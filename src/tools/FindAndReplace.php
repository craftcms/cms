<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use Craft;
use craft\app\base\Tool;
use craft\app\tasks\FindAndReplace as FindAndReplaceTask;

/**
 * FindAndReplace represents a Find and Replace tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FindAndReplace extends Tool
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app','Find and Replace');
	}

	/**
	 * @inheritdoc
	 */
	public static function iconValue()
	{
		return 'wand';
	}

	/**
	 * @inheritdoc
	 */
	public static function optionsHtml()
	{
		return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
			[
				'name'        => 'find',
				'placeholder' => Craft::t('app','Find'),
			]
		]) .
		Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
			[
				'name'        => 'replace',
				'placeholder' => Craft::t('app','Replace'),
			]
		]);
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function performAction($params = [])
	{
		if (!empty($params['find']) && !empty($params['replace']))
		{
			Craft::$app->getTasks()->queueTask([
				'type'    => FindAndReplaceTask::className(),
				'find'    => $params['find'],
				'replace' => $params['replace']
			]);
		}
	}
}
