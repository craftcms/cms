<?php
namespace Blocks;

/**
 *
 */
class FeedWidget extends BaseWidget
{
	public $name = 'Feed';
	public $multipleInstances = true;

	public $items = array();

	protected $bodyTemplate = '_widgets/FeedWidget/body';
	protected $settingsTemplate = '_widgets/FeedWidget/settings';

	protected $defaultSettings = array(
		'url'   => 'http://feeds.feedburner.com/blogandtonic',
		'title' => 'Blog &amp; Tonic',
		'limit' => 5
	);

	/**
	 * Returns the widget title.
	 * @return string
	 */
	public function title()
	{
		if (isset($this->record))
		$settings = $this->record->getSettings();
		return $settings['title'];
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

		$limit = $this->settings['limit'];
		$items = $feed->get_items(0, $limit);
		foreach ($items as $item)
		{
			$this->items[] = array(
				'url'   => $item->get_permalink(),
				'title' => $item->get_title(),
				'date'  => new DateTime('@'.$item->get_date('U'))
			);
		}

		return parent::displayBody();
	}
}
