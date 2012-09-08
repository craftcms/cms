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
	 * Defines the widget settings.
	 *
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'url'   => array(AttributeType::Url, 'default' => 'http://feeds.feedburner.com/blogandtonic'),
			'title' => array(AttributeType::Name, 'default' => 'Blog & Tonic'),
			'limit' => array(AttributeType::Number, 'min' => 0, 'default' => 5),
		);
	}

	/**
	 * Returns the widget's widget HTML.
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return TemplateHelper::render('_components/widgets/FeedWidget/settings', array(
			'settings' => $this->settings
		));
	}

	/**
	 * Gets the widget's title.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getTitle()
	{
		return $this->settings->title;
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getBodyHtml()
	{
		return TemplateHelper::render('_components/widgets/FeedWidget/body', array(
			'items' => $this->_getItems()
		));
	}

	/**
	 * Gets the feed items.
	 *
	 * @access private
	 * @return array
	 */
	private function _getItems()
	{
		$items = array();

		$url = $this->settings['url'];
		$cachePath = blx()->path->getCachePath();
		$feed = new \SimplePie($url, $cachePath);
		$feed->init();
		$feed->handle_content_type();

		$limit = $this->settings->limit;

		foreach ($feed->get_items(0, $limit) as $item)
		{
			$items[] = array(
				'url'   => $item->get_permalink(),
				'title' => $item->get_title(),
				'date'  => new DateTime('@'.$item->get_date('U'))
			);
		}

		return $items;
	}

}
