<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use Craft;
use craft\app\base\Widget;

/**
 * GetHelp represents a Get Help dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GetHelp extends Widget
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Get Help');
	}

	/**
	 * @inheritdoc
	 */
	public static function isSelectable()
	{
		// Only admins get the Get Help widget.
		return (parent::isSelectable() && Craft::$app->getUser()->getIsAdmin());
	}

	/**
	 * @inheritdoc
	 */
	protected static function allowMultipleInstances()
	{
		return false;
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTitle()
	{
		return Craft::t('app', 'Send a message to Craft Support');
	}

	/**
	 * @inheritdoc
	 */
	public function getBodyHtml()
	{
		// Only admins get the Get Help widget.
		if (!Craft::$app->getUser()->getIsAdmin())
		{
			return false;
		}

		$js = "new Craft.GetHelpWidget({$this->id});";
		Craft::$app->getView()->registerJs($js);

		Craft::$app->getView()->registerJsResource('js/GetHelpWidget.js');
		Craft::$app->getView()->includeTranslations('Message sent successfully.', 'Couldn’t send support request.');

		return Craft::$app->getView()->renderTemplate('_components/widgets/GetHelp/body');
	}
}
