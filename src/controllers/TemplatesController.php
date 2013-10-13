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
			if (!IOHelper::fileExists(craft()->path->getSiteTemplatesPath().'offline.html'))
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

	/**
	 * Renders the Breakpoint Update notification template.
	 */
	public function actionBreakpointUpdateNotification()
	{
		$this->_render('_special/breakpointupdate', array(
			'minBuild'      => CRAFT_MIN_BUILD_REQUIRED,
			'minBuildURL'   => CRAFT_MIN_BUILD_URL,
			'targetVersion' => CRAFT_VERSION,
			'targetBuild'   => CRAFT_BUILD
		));
	}

	public function actionRequirementsCheck()
	{
		// Run the requirements checker
		$reqCheck = new RequirementsChecker();
		$reqCheck->run();

		if ($reqCheck->getResult() == InstallStatus::Failure)
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

				throw new Exception(Craft::t('The update can’t be installed. :( {message}', array('message' => $message)));
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
			craft()->fileCache->set('appPath', craft()->path->getAppPath());
		}
	}

	/**
	 * Renders an error template.
	 */
	public function actionRenderError()
	{
		$error = craft()->errorHandler->getError();
		$template = (string) $error['code'];

		if (craft()->request->isSiteRequest())
		{
			if (!craft()->templates->doesTemplateExist($template))
			{
				// How bout a generic error template?
				if (craft()->templates->doesTemplateExist('error'))
				{
					$template = 'error';
				}
				else
				{
					// Fall back on the CP error template
					craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());

					// Look for the template again
					if (!craft()->templates->doesTemplateExist($template))
					{
						$template = 'error';
					}
				}
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
