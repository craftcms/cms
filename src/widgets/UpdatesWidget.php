<?php
namespace Craft;

/**
 *
 */
class UpdatesWidget extends BaseWidget
{
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
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		// Todo: Remove for Craft 1.3 since this widget won't show up to begin with.
		if (!craft()->userSession->checkPermission('performUpdates'))
		{
			return '<p>Sorry, you’re not allowed to perform updates.</p>';
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
