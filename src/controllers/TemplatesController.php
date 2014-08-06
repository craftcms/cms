<?php
namespace Craft;

/**
 * Class TemplatesController
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class TemplatesController extends BaseController
{
	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in the array list.
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require login on a few, it's best to use craft()->userSession->requireLogin() in the individual methods.
	 *
	 * Any permissions not covered in actionRender() should be handled byt the templates.
	 *
	 * @var bool
	 */
	public $allowAnonymous = true;

	/**
	 * Renders a template.
	 *
	 * @param       $template
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return void
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
	 * Renders the Manual Update notification template.
	 *
	 * @return void
	 */
	public function actionManualUpdateNotification()
	{
		$this->renderTemplate('_special/dbupdate');
	}

	/**
	 * Renders the Manual Update template.
	 *
	 * @return void
	 */
	public function actionManualUpdate()
	{
		$this->renderTemplate('updates/_go', array(
			'handle' => craft()->request->getSegment(2)
		));
	}

	/**
	 * @throws Exception
	 * @return void
	 */
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
	 *
	 * @throws \Exception
	 * @return void
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
			else if ($code == 503 && craft()->templates->doesTemplateExist($prefix.'offline'))
			{
				$template = $prefix.'offline';
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
