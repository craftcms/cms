<?php
namespace Craft;

/**
 * Get Help widget.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.widgets
 * @since     1.0
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
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Get Help');
	}

	/**
	 * @inheritDoc IWidget::getTitle()
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('Send a message to Craft CMS Support');
	}

	/**
	 * @inheritDoc IWidget::getIconPath()
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return craft()->path->getResourcesPath().'images/widgets/get-help.svg';
	}

	/**
	 * @inheritDoc IWidget::getBodyHtml()
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
	 * @inheritDoc IComponentType::isSelectable()
	 *
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
