<?php
namespace Craft;

/**
 *
 */
class UpdatesWidget extends BaseWidget
{
	/**
	 * @access protected
	 * @var bool Whether users should be able to select more than one of this widget type.
	 */
	protected $multi = false;

	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Updates');
	}

	/**
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
	 * Returns the widget's body HTML.
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
