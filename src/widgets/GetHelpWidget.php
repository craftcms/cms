<?php
namespace Craft;

/**
 * Get Help widget.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.widgets
 * @since     1.0
 */
class GetHelpWidget extends BaseWidget
{
	////////////////////
	// PROPERTIES
	////////////////////

	/**
	 * @var bool Whether users should be able to select more than one of this widget type.
	 */
	protected $multi = false;

	////////////////////
	// PUBLIC METHODS
	////////////////////

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
	 * Returns the widget's body HTML.
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		// Only admins get the Get Help widget.
		if (!craft()->userSession->isAdmin())
		{
			return false;
		}

		$id = $this->model->id;
		$js = "new Craft.GetHelpWidget({$id});";
		craft()->templates->includeJs($js);

		craft()->templates->includeJsResource('js/GetHelpWidget.js');
		craft()->templates->includeTranslations('Message sent successfully.', 'Couldn’t send support request.');

		return craft()->templates->render('_components/widgets/GetHelp/body');
	}

	/**
	 * @return bool
	 */
	public function isSelectable()
	{
		// Only admins get the Get Help widget.
		if (parent::isSelectable() && craft()->userSession->isAdmin())
		{
			return true;
		}

		return false;
	}
}
