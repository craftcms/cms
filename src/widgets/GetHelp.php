<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use Craft;

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
		return Craft::t('app', 'Get Help');
	}

	/**
	 * @inheritDoc WidgetInterface::getTitle()
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('app', 'Send a message to Craft Support');
	}

	/**
	 * @inheritDoc WidgetInterface::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		// Only admins get the Get Help widget.
		if (!Craft::$app->getUser()->getIsAdmin())
		{
			return false;
		}

		$id = $this->model->id;
		$js = "new Craft.GetHelpWidget({$id});";
		Craft::$app->templates->includeJs($js);

		Craft::$app->templates->includeJsResource('js/GetHelpWidget.js');
		Craft::$app->templates->includeTranslations('Message sent successfully.', 'Couldn’t send support request.');

		return Craft::$app->templates->render('_components/widgets/GetHelp/body');
	}

	/**
	 * @inheritDoc ComponentTypeInterface::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		// Only admins get the Get Help widget.
		if (parent::isSelectable() && Craft::$app->getUser()->getIsAdmin())
		{
			return true;
		}

		return false;
	}
}
