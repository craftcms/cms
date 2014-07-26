<?php
namespace Craft;

/**
 * Class FeedWidget
 *
 * @package craft.app.widgets
 */
class FeedWidget extends BaseWidget
{
	public $multipleInstances = true;

	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Feed');
	}

	/**
	 * Defines the settings.
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

	/**
	 * Returns the widget's body HTML.
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
	 * Gets the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->settings->title;
	}

	/**
	 * Returns the widget's body HTML.
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
}
