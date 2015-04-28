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
 * Updates represents an Updates dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Updates extends Widget
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Updates');
	}

	/**
	 * @inheritdoc
	 */
	public static function isSelectable()
	{
		// Gotta have update permission to get this widget
		return (parent::isSelectable() && Craft::$app->getUser()->checkPermission('performUpdates'));
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
	public function getBodyHtml()
	{
		// Make sure the user actually has permission to perform updates
		if (!Craft::$app->getUser()->checkPermission('performUpdates'))
		{
			return false;
		}

		$cached = Craft::$app->getUpdates()->isUpdateInfoCached();

		if (!$cached || !Craft::$app->getUpdates()->getTotalAvailableUpdates())
		{
			Craft::$app->getView()->registerJsResource('js/UpdatesWidget.js');
			Craft::$app->getView()->registerJs('new Craft.UpdatesWidget('.$this->id.', '.($cached ? 'true' : 'false').');');

			Craft::$app->getView()->includeTranslations(
				'One update available!',
				'{total} updates available!',
				'Go to Updates',
				'Congrats! You’re up-to-date.',
				'Check again'
			);
		}

		if ($cached)
		{
			return Craft::$app->getView()->renderTemplate('_components/widgets/Updates/body', [
				'total' => Craft::$app->getUpdates()->getTotalAvailableUpdates()
			]);
		}
		else
		{
			return '<p class="centeralign">'.Craft::t('app', 'Checking for updates…').'</p>';
		}
	}
}
