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
			'targetVersion' => CRAFT_VERSION,
			'targetBuild'   => CRAFT_BUILD
		));
	}

	/**
	 * Renders the Invalid Track template.
	 */
	public function actionInvalidTrack()
	{
		$this->_render('_special/invalidtrack');
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
