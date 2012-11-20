<?php
namespace Blocks;

/**
 * Get Help widget
 */
class GetHelpWidget extends BaseWidget
{
	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Get Help');
	}

	/**
	 * Gets the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Blocks::t('Send a message to Blocks Support');
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		$id = $this->model->id;
		$js = "new Blocks.GetHelpWidget({$id});";
		blx()->templates->includeJs($js);

		blx()->templates->includeJsResource('js/GetHelpWidget.js');
		blx()->templates->includeTranslations('Message sent successfully.');


		$message = "Enter your message here.\n\n" .
			"------------------------------\n\n" .
			'Blocks version: '.Blocks::getVersion().' build '.Blocks::getBuild()."\n" .
			'Packages: '.implode(', ', Blocks::getPackages());

		$plugins = blx()->plugins->getPlugins();

		if ($plugins)
		{
			foreach ($plugins as $plugin)
			{
				$pluginNames[] = $plugin->getName().' ('.$plugin->getDeveloper().')';
			}

			$message .= "\nPlugins: ".implode(', ', $pluginNames);
		}

		return blx()->templates->render('_components/widgets/GetHelp/body', array(
			'message' => $message
		));
	}
}
