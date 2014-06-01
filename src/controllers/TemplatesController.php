<?php
namespace Craft;

/**
 *
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
		$this->_render($template, $variables);
	}

	/**
	 * Shows the 'offline' template.
	 */
	public function actionOffline()
	{
		// If this is a site request, make sure the offline template exists
		if (craft()->request->isSiteRequest())
		{
			$extensions = craft()->config->get('defaultTemplateExtensions');
			$foundMatch = false;

			foreach ($extensions as $extension)
			{
				if (IOHelper::fileExists(craft()->path->getSiteTemplatesPath().'offline.'.$extension))
				{
					$foundMatch = true;
					break;
				}
			}

			if (!$foundMatch)
			{
				// Set PathService to use the CP templates path instead
				craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());
			}
		}

		// Output the offline template
		$this->_render('offline');
	}

	/**
	 * Renders the Manual Update notification template.
	 */
	public function actionManualUpdateNotification()
	{
		$this->_render('_special/dbupdate');
	}

	/**
	 * Renders the Manual Update template.
	 */
	public function actionManualUpdate()
	{
		$this->_render('updates/_go', array(
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
				$this->_render('_special/cantrun', array('reqCheck' => $reqCheck));
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

	/**
	 * Renders a template, sets the mime type header, etc..
	 *
	 * @access private
	 * @param string     $template
	 * @param array|null $variables
	 * @throws HttpException
	 * @throws TemplateLoaderException|\Exception
	 */
	private function _render($template, $variables = array())
	{
		try
		{
			$this->renderTemplate($template, $variables);
		}
		catch (TemplateLoaderException $e)
		{
			if ($e->template == $template)
			{
				throw new HttpException(404);
			}
			else
			{
				throw $e;
			}
		}
	}
}
