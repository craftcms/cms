<?php
namespace Blocks;

/**
 *
 */
class FeedsService extends BaseApplicationComponent
{
	/**
	 * Returns the items for the Feed widget.
	 *
	 * @param string|array $url
	 * @param int          $limit
	 * @param int          $offset
	 * @return array
	 */
	public function getFeedItems($url, $limit = 0, $offset = 0)
	{
		$items = array();

		$this->_registerSimplePieAutoloader();
		$feed = new \SimplePie();
		$feed->set_feed_url($url);
		$feed->set_cache_location(blx()->path->getCachePath());
		$feed->set_cache_duration(blx()->config->getCacheDuration());
		$feed->init();
		//$feed->handle_content_type();

		foreach ($feed->get_items(0, $limit) as $item)
		{
			$items[] = array(
				'authors'      => $this->_getItemAuthors($item->get_authors()),
				'categories'   => $this->_getItemCategories($item->get_categories()),
				'content'      => $item->get_content(true),
				'contributors' => $this->_getItemAuthors($item->get_contributors()),
				'date'         => new DateTime('@'.$item->get_date('U')),
				'dateUpdated'  => new DateTime('@'.$item->get_updated_date('U')),
				'permalink'    => $item->get_permalink(),
				'summary'      => $item->get_description(true),
				'title'        => $item->get_title(),
			);
		}

		return $items;
	}

	/**
	 * Returns an array of authors.
	 *
	 * @access private
	 * @param array $objects
	 * @return array
	 */
	private function _getItemAuthors($objects)
	{
		$authors = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$authors[] = array(
					'name'  => $object->get_name(),
					'url'   => $object->get_link(),
					'email' => $object->get_email(),
				);
			}
		}

		return $authors;
	}

	/**
	 * Returns an array of categories.
	 *
	 * @access private
	 * @param array $objects
	 * @return array
	 */
	private function _getItemCategories($objects)
	{
		$categories = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$categories[] = array(
					'term'   => $object->get_term(),
					'scheme' => $object->get_scheme(),
					'label'  => $object->get_label(),
				);
			}
		}

		return $categories;
	}

	/**
	 * Registers the SimplePie autoloader.
	 *
	 * @access private
	 */
	private function _registerSimplePieAutoloader()
	{
		if (!class_exists('\SimplePie_Autoloader', false))
		{
			require_once blx()->path->getLibPath().'SimplePie/autoloader.php';
			Blocks::registerAutoloader(array(new \SimplePie_Autoloader, 'autoload'));

			// Did it work?
			if (!class_exists('\SimplePie'))
			{
				throw new Exception(Blocks::t('The SimplePie autoloader was not registered properly.'));
			}
		}
	}
}
