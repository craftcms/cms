<?php
namespace Craft;

/**
 * Class TemplatesController
 *
 * @package craft.app.controllers
 */
class TemplatesController extends BaseController
{
	// Any permissions not covered in actionRender() should be handled by the templates
	public $allowAnonymous = true;

	/**
	 * Renders a template.
	 */
	public function actionRender($template, array $variables = array())
	{
		// Does that template exist?
		if (craft()->templates->doesTemplateExist($template))
		{
			$this->renderTemplate($template, $variables);
		}
		else
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Shows the 'offline' template.
	 */
	public function actionOffline()
	{
		// If this is a site request, make sure the offline template exists
		if (craft()->request->isSiteRequest() && !craft()->templates->doesTemplateExist('offline'))
		{
			// Set PathService to use the CP templates path instead
			craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());
		}

		// Output the offline template
		$this->renderTemplate('offline');
	}

	/**
	 * Renders the Manual Update notification template.
	 */
	public function actionManualUpdateNotification()
	{
		$this->renderTemplate('_special/dbupdate');
	}

	/**
	 * Renders the Manual Update template.
	 */
	public function actionManualUpdate()
	{
		$this->renderTemplate('updates/_go', array(
			'handle' => craft()->request->getSegment(2)
		));
	}

	public function actionRequirementsCheck()
	{
		// Run the requirements checker
		$reqCheck = new RequirementsChecker();
		$reqCheck->run();

		if ($reqCheck->getResult() == InstallStatus::Failed)
		{
			// Coming from Updater.php
			if (craft()->request->isAjaxRequest())
			{
				$message = '<br /><br />';

				foreach ($reqCheck->getRequirements() as $req)
				{
					if ($req->result == 'failed')
					{
						$message .= $req->notes.'<br />';
					}
				}

				throw new Exception(Craft::t('The update can’t be installed :( {message}', array('message' => $message)));
			}
			else
			{
				$this->renderTemplate('_special/cantrun', array('reqCheck' => $reqCheck));
				craft()->end();
			}


		}
		else
		{
			// Cache the app path.
			craft()->cache->set('appPath', craft()->path->getAppPath());
		}
	}

	/**
	 * Renders an error template.
	 */
	public function actionRenderError()
	{
		$error = craft()->errorHandler->getError();
		$code = (string) $error['code'];

		if (craft()->request->isSiteRequest())
		{
			$prefix = craft()->config->get('errorTemplatePrefix');

			if (craft()->templates->doesTemplateExist($prefix.$code))
			{
				$template = $prefix.$code;
			}
			else if (craft()->templates->doesTemplateExist($prefix.'error'))
			{
				$template = $prefix.'error';
			}
		}

		if (!isset($template))
		{
			craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());

			if (craft()->templates->doesTemplateExist($code))
			{
				$template = $code;
			}
			else
			{
				$template = 'error';
			}
		}

		try
		{
			$this->renderTemplate($template, $error);
		}
		catch (\Exception $e)
		{
			if (YII_DEBUG)
			{
				throw $e;
			}
			else
			{
				// Just output the error message
				echo $e->getMessage();
			}
		}
	}
}
