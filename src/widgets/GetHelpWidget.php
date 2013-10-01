<?php
namespace Craft;

/**
 * Get Help widget
 */
class GetHelpWidget extends BaseWidget
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
		return Craft::t('Get Help');
	}

	/**
	 * Gets the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('Send a message to @@@appName@@@ Support');
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		$id = $this->model->id;
		$js = "new Craft.GetHelpWidget({$id});";
		craft()->templates->includeJs($js);

		craft()->templates->includeJsResource('js/GetHelpWidget.js');
		craft()->templates->includeTranslations('Message sent successfully.', 'Couldn’t send support request.');


		$message = "Enter your message here.\n\n" .
			"------------------------------\n\n" .
			'@@@appName@@@ version: ' .
			Craft::t('{version} build {build}', array(
				'version' => craft()->getVersion(),
				'build'   => craft()->getBuild()
			))."\n" .
			'Packages: '.implode(', ', craft()->getPackages());

		$plugins = craft()->plugins->getPlugins();

		if ($plugins)
		{
			$pluginNames = array();

			foreach ($plugins as $plugin)
			{
				$pluginNames[] = $plugin->getName().' ('.$plugin->getDeveloper().')';
			}

			$message .= "\nPlugins: ".implode(', ', $pluginNames);
		}

		return craft()->templates->render('_components/widgets/GetHelp/body', array(
			'message' => $message
		));
	}
}
