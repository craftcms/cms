<?php
namespace Blocks;

/**
 *
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
		return Blocks::t('Feed');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
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
		return blx()->templates->render('_components/widgets/Feed/settings', array(
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
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		$id = $this->model->id;
		$url = JsonHelper::encode($this->getSettings()->url);
		$limit = $this->getSettings()->limit;

		$js = "new Blocks.FeedWidget({$id}, {$url}, {$limit});";

		blx()->templates->includeJsResource('js/FeedWidget.js');
		blx()->templates->includeJs($js);

		return blx()->templates->render('_components/widgets/Feed/body', array(
			'limit' => $limit
		));
	}
}
