<?php
namespace Blocks;

/**
 *
 */
class FeedWidget extends BaseWidget
{
	public $name = 'Feed';
	public $multipleInstances = true;

	public $items;

	protected $bodyTemplate = '_widgets/FeedWidget/body';
	protected $settingsTemplate = '_widgets/FeedWidget/settings';

	protected $settings = array(
		'url'   => 'http://feeds.feedburner.com/blogandtonic',
		'title' => 'Blog &amp; Tonic',
		'limit' => 5
	);

	/**
	 * Gets the widget title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->settings['title'];
	}

	/**
	 * Gets the widget body.
	 *
	 * @return string
	 */
	public function getBody()
	{
		$url = $this->settings['url'];
		$cachePath = blx()->path->getCachePath();
		$feed = new \SimplePie($url, $cachePath);
		$feed->init();
		$feed->handle_content_type();

		$limit = $this->settings['limit'];
		$items = $feed->get_items(0, $limit);

		$this->items = array();
		foreach ($items as $item)
		{
			$this->items[] = array(
				'url'   => $item->get_permalink(),
				'title' => $item->get_title(),
				'date'  => new DateTime('@'.$item->get_date('U'))
			);
		}

		return parent::getBody();
	}
}
