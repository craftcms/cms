<?php
namespace Craft;

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

		if (!extension_loaded('dom'))
		{
			Craft::log('Craft needs the PHP DOM extension (http://www.php.net/manual/en/book.dom.php) enabled to parse feeds.', LogLevel::Warning);
			return $items;
		}

		$feed = new \SimplePie();
		$feed->set_feed_url($url);
		$feed->set_cache_location(craft()->path->getCachePath());
		$feed->set_cache_duration(craft()->config->getCacheDuration());
		$feed->init();
		//$feed->handle_content_type();

		foreach ($feed->get_items(0, $limit) as $item)
		{
			$date = $item->get_date('U');
			$dateUpdated = $item->get_updated_date('U');

			$items[] = array(
				'authors'      => $this->_getItemAuthors($item->get_authors()),
				'categories'   => $this->_getItemCategories($item->get_categories()),
				'content'      => $item->get_content(true),
				'contributors' => $this->_getItemAuthors($item->get_contributors()),
				'date'         => ($date ? new DateTime('@'.$date) : null),
				'dateUpdated'  => ($dateUpdated ? new DateTime('@'.$dateUpdated) : null),
				'permalink'    => $item->get_permalink(),
				'summary'      => $item->get_description(true),
				'title'        => $item->get_title(),
				'enclosures'   => $this->_getEnclosures($item->get_enclosures()),
			);
		}

		return $items;
	}

	/**
	 * @param $objects
	 * @return array
	 */
	private function _getEnclosures($objects)
	{
		$enclosures = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$enclosures[] = array(
					'bitrate'      => $object->get_bitrate(),
					'captions'     => $this->_getCaptions($object->get_captions()),
					'categories'   => $this->_getCategories($object->get_categories()),
					'channels'     => $object->get_channels(),
					'copyright'    => $object->get_copyright(),
					'credits'      => $this->_getCredits($object->get_credits()),
					'description'  => $object->get_description(),
					'duration'     => $object->get_duration(),
					'expression'   => $object->get_expression(),
					'extension'    => $object->get_extension(),
					'framerate'    => $object->get_framerate(),
					'handler'      => $object->get_handler(),
					'hashes'       => $object->get_hashes(),
					'height'       => $object->get_height(),
					'language'     => $object->get_language(),
					'keywords'     => $object->get_keywords(),
					'length'       => $object->get_length(),
					'link'         => $object->get_link(),
					'medium'       => $object->get_medium(),
					'player'       => $object->get_player(),
					'ratings'      => $this->_getRatings($object->get_ratings()),
					'restrictions' => $this->_getRestrictions($object->get_restrictions()),
					'samplingRate' => $object->get_sampling_rate(),
					'size'         => $object->get_size(),
					'thumbnails'   => $object->get_thumbnails(),
					'title'        => $object->get_title(),
					'type'         => $object->get_type(),
					'width'        => $object->get_width(),
				);
			}
		}

		return $enclosures;
	}

	/**
	 * @param $objects
	 * @return array
	 */
	private function _getRatings($objects)
	{
		$ratings = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$ratings[] = array(
					'scheme'   => $object->get_scheme(),
					'value'  => $object->get_value(),
				);
			}
		}

		return $ratings;
	}

	/**
	 * @param $objects
	 * @return array
	 */
	private function _getRestrictions($objects)
	{
		$restrictions = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$restrictions[] = array(
					'relationship' => $object->get_relationship(),
					'type'         => $object->get_type(),
					'value'        => $object->get_value(),
				);
			}
		}

		return $restrictions;
	}

	/**
	 * @param $objects
	 * @return array
	 */
	private function _getCaptions($objects)
	{
		$captions = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$captions[] = array(
					'endtime'   => $object->get_endtime(),
					'language'  => $object->get_language(),
					'starttime' => $object->get_starttime(),
					'text'      => $object->get_text(),
					'type'      => $object->get_type(),
				);
			}
		}

		return $captions;
	}

	/**
	 * @param $objects
	 * @return array
	 */
	private function _getCredits($objects)
	{
		$credits = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$credits[] = array(
					'role'   => $object->get_role(),
					'scheme' => $object->get_scheme(),
					'name'   => $object->get_name(),
				);
			}
		}

		return $credits;
	}

	/**
	 * @param $objects
	 * @return array
	 */
	private function _getCategories($objects)
	{
		$categories = array();

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$categories[] = array(
					'term'   => $object->get_term(),
					'scheme'  => $object->get_scheme(),
					'label' => $object->get_label(),
				);
			}
		}

		return $categories;
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
}
