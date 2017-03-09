<?php
namespace Craft;

/**
 * Class NewUsersWidget
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.widgets
 * @since     1.0
 */
class NewUsersWidget extends BaseWidget
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('New Users');
	}

	/**
	 * @inheritDoc IComponentType::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		// This widget is only available for Craft Pro
		return (craft()->getEdition() == Craft::Pro);
	}

	/**
	 * @inheritDoc IWidget::getTitle()
	 *
	 * @return string
	 */
	public function getTitle()
	{
		if ($userGroupId = $this->getSettings()->userGroupId)
		{
			$userGroup = craft()->userGroups->getGroupById($userGroupId);

			if ($userGroup)
			{
				return Craft::t('New Users').' – '.Craft::t($userGroup->name);
			}
		}

		return parent::getTitle();
	}

	/**
	 * @inheritDoc IWidget::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		if (craft()->getEdition() != Craft::Pro)
		{
			return false;
		}

		$settings = $this->getSettings();

		$groupId = $settings->userGroupId;
		$userGroup = craft()->userGroups->getGroupById($groupId);

		$options = $settings->getAttributes();
		$options['orientation'] = craft()->locale->getOrientation();

		craft()->templates->includeJsResource('js/NewUsersWidget.js');
		craft()->templates->includeJs('new Craft.NewUsersWidget('.$this->model->id.', '.JsonHelper::encode($options).');');

		$dateRange = false;
		$dateRanges = ChartHelper::getDateRanges();

		if(isset($dateRanges[$settings->dateRange]))
		{
			$dateRange = $dateRanges[$settings->dateRange];
		}

		return '<div></div>';
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/widgets/NewUsers/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * @inheritDoc IWidget::getIconPath()
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return craft()->path->getResourcesPath().'images/widgets/new-users.svg';
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'userGroupId'   => AttributeType::Number,
			'dateRange'   => AttributeType::String,
		);
	}
}
