<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use craft\app\Craft;

/**
 * Get Help widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GetHelpWidget extends BaseWidget
{
	// Properties
	// =========================================================================

	/**
	 * Whether users should be able to select more than one of this widget type.
	 *
	 * @var bool
	 */
	protected $multi = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Get Help');
	}

	/**
	 * @inheritDoc WidgetInterface::getTitle()
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('Send a message to @@@appName@@@ Support');
	}

	/**
	 * @inheritDoc WidgetInterface::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		// Only admins get the Get Help widget.
		if (!craft()->getUser()->getIsAdmin())
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
	 * @inheritDoc ComponentTypeInterface::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		// Only admins get the Get Help widget.
		if (parent::isSelectable() && craft()->getUser()->getIsAdmin())
		{
			return true;
		}

		return false;
	}
}
