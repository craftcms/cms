<?php
namespace Craft;

/**
 * Class UpdatesWidget
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.widgets
 * @since     1.0
 */
class UpdatesWidget extends BaseWidget
{
	// Properties
	// =========================================================================

	/**
	 * Whether users should be able to select more than one of this widget type.
	 *
	 * @var bool
	 */
	protected $multi = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Updates');
	}

	/**
	 * @inheritDoc IComponentType::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		// Gotta have update permission to get this widget
		if (parent::isSelectable() && craft()->userSession->checkPermission('performUpdates'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc IWidget::getIconPath()
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return craft()->path->getResourcesPath().'images/widgets/updates.svg';
	}

	/**
	 * @inheritDoc IWidget::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		// Make sure the user actually has permission to perform updates
		if (!craft()->userSession->checkPermission('performUpdates'))
		{
			return false;
		}

		$cached = craft()->updates->isUpdateInfoCached();

		if (!$cached || !craft()->updates->getTotalAvailableUpdates())
		{
			craft()->templates->includeJsResource('js/UpdatesWidget.js');
			craft()->templates->includeJs('new Craft.UpdatesWidget('.$this->model->id.', '.($cached ? 'true' : 'false').');');

			craft()->templates->includeTranslations(
				'One update available!',
				'{total} updates available!',
				'Go to Updates',
				'Congrats! You’re up-to-date.',
				'Check again'
			);
		}

		if ($cached)
		{
			return craft()->templates->render('_components/widgets/Updates/body', array(
				'total' => craft()->updates->getTotalAvailableUpdates()
			));
		}
		else
		{
			return '<p class="centeralign">'.Craft::t('Checking for updates…').'</p>';
		}
	}
}
