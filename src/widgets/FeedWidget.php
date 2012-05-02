<?php
namespace Blocks;

/**
 *
 */
class FeedWidget extends Widget
{
	public $widgetName = 'Feed';

	public $items = array();

	protected $bodyTemplate = '_widgets/FeedWidget/body';
	protected $settingsTemplate = '_widgets/FeedWidget/settings';

	protected $defaultSettings = array(
		'url'   => 'http://feeds.feedburner.com/blogandtonic',
		'title' => 'Blog &amp; Tonic',
		'limit' => 5
	);

	/**
	 * @access protected
	 */
	public function init()
	{
		$this->title = $this->settings['title'];
	}

	/**
	 * @return mixed
	 */
	public function displayBody()
	{
		$url = $this->settings['url'];
		$cachePath = b()->path->getCachePath();
		$feed = new \SimplePie($url, $cachePath);
		$feed->init();
		$feed->handle_content_type();

		foreach ($feed->get_items() as $item)
		{
			$date = new DateTime;
			$date->setTimestamp($item->get_date('U'));

			$this->items[] = array(
				'url' => $item->get_permalink(),
				'title' => $item->get_title(),
				'date' => $date
			);
		}

		return parent::displayBody();
	}
}
