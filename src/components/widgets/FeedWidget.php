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
			'url'   => array(AttributeType::Url, 'required' => true),
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
		try{
		return TemplateHelper::render('_components/widgets/FeedWidget/settings', array(
			'settings' => new ModelVariable($this->getSettings())
		));
		} catch (\Exception $e)
		{
			print_r($e); die();
		}
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
