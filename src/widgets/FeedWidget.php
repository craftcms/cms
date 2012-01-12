<?php

/**
 *
 */
class FeedWidget extends Widget
{
	public $className = 'rss';

	public $settings = array(
		'url' => 'http://feeds.feedburner.com/blogandtonic',
		'title' => 'Blog &amp; Tonic',
		'limit' => 5
	);

	/**
	 * @access protected
	 */
	protected function init()
	{
		$this->title = $this->settings['title'];
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function displayBody()
	{
		$tags = array(
			'items' => array(
				array(
					'url' => 'http://feedproxy.google.com/~r/blogandtonic/~3/KrG7zZ_5zF0/assets-moty',
					'title' => 'Assets Voted Module of the Year',
					'date' => 'Dec 22, 2011'
				),
				array(
					'url' => 'http://feedproxy.google.com/~r/blogandtonic/~3/WXqTFhOZP0o/php-developer',
					'title' => 'We’re Looking for a PHP Developer',
					'date' => 'Dec 2, 2011'
				),
				array(
					'url' => 'http://feedproxy.google.com/~r/blogandtonic/~3/hwoW_CDbEF8/introducing-assets',
					'title' => 'Introducing Assets',
					'date' => 'Jun 28, 2011'
				),
				array(
					'url' => 'http://feedproxy.google.com/~r/blogandtonic/~3/D2fEpY3KITQ/wygwam22',
					'title' => 'Wygwam 2.2 Released!',
					'date' => 'Feb 9, 2011'
				),
				array(
					'url' => 'http://feedproxy.google.com/~r/blogandtonic/~3/To6-CuOmKlA/playa4',
					'title' => 'Playa 4 has arrived!',
					'date' => 'Feb 2, 2011'
				)
			)
		);

		return Blocks::app()->controller->loadTemplate('_widgets/FeedWidget/body', $tags, true);
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function displaySettings()
	{
		$tags = array(
			'settings' => $this->settings
		);

		return Blocks::app()->controller->loadTemplate('_widgets/FeedWidget/settings', $tags, true);
	}
}
