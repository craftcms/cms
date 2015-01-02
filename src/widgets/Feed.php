<?php
namespace craft\app\widgets;

use craft\app\Craft;

/**
 * Class Feed widget.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.widgets
 * @since     3.0
 */
class Feed extends BaseWidget
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	public $multipleInstances = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Feed');
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/widgets/Feed/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * @inheritDoc WidgetInterface::getTitle()
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->settings->title;
	}

	/**
	 * @inheritDoc WidgetInterface::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		$id = $this->model->id;
		$url = JsonHelper::encode($this->getSettings()->url);
		$limit = $this->getSettings()->limit;

		$js = "new Craft.FeedWidget({$id}, {$url}, {$limit});";

		craft()->templates->includeJsResource('js/FeedWidget.js');
		craft()->templates->includeJs($js);

		return craft()->templates->render('_components/widgets/Feed/body', array(
			'limit' => $limit
		));
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
			'url'   => array(AttributeType::Url, 'required' => true, 'label' => 'URL'),
			'title' => array(AttributeType::Name, 'required' => true),
			'limit' => array(AttributeType::Number, 'min' => 0, 'default' => 5),
		);
	}
}
