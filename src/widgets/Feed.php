<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\helpers\JsonHelper;

/**
 * Class Feed widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		return Craft::$app->templates->render('_components/widgets/Feed/settings', [
			'settings' => $this->getSettings()
		]);
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

		Craft::$app->templates->includeJsResource('js/FeedWidget.js');
		Craft::$app->templates->includeJs($js);

		return Craft::$app->templates->render('_components/widgets/Feed/body', [
			'limit' => $limit
		]);
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
		return [
			'url'   => [AttributeType::Url, 'required' => true, 'label' => 'URL'],
			'title' => [AttributeType::Name, 'required' => true],
			'limit' => [AttributeType::Number, 'min' => 0, 'default' => 5],
		];
	}
}
