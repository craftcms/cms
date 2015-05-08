<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\dates\DateTime;
use craft\app\helpers\DateTimeHelper;
use craft\app\models\Url;
use yii\base\Component;

/**
 * The Feeds service provides APIs for fetching remote RSS and Atom feeds.
 *
 * An instance of the Feeds service is globally accessible in Craft via [[Application::feeds `Craft::$app->getFeeds()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Feeds extends Component
{
	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		parent::init();

		// Import this here to ensure that libs like SimplePie are using our version of the class and not any server's
		// random version.
		require_once(Craft::getAlias('@app/vendor/simplepie/simplepie/idn/idna_convert.class.php'));
	}

	/**
	 * Fetches and parses an RSS or Atom feed, and returns its items.
	 *
	 * Each element in the returned array will have the following keys:
	 *
	 * - **authors** – An array of the item’s authors, where each sub-element has the following keys:
	 *     - **name** – The author’s name
	 *     - **url** – The author’s URL
	 *     - **email** – The author’s email
	 * - **categories** – An array of the item’s categories, where each sub-element has the following keys:
	 *     - **term** – The category’s term
	 *     - **scheme** – The category’s scheme
	 *     - **label** – The category’s label
	 * - **content** – The item’s main content.
	 * - **contributors** – An array of the item’s contributors, where each sub-element has the following keys:
	 *     - **name** – The contributor’s name
	 *     - **url** – The contributor’s URL
	 *     - **email** – The contributor’s email
	 * - **date** – A [[DateTime]] object representing the item’s date.
	 * - **dateUpdated** – A [[DateTime]] object representing the item’s last updated date.
	 * - **permalink** – The item’s URL.
	 * - **summary** – The item’s summary content.
	 * - **title** – The item’s title.
	 *
	 * @param string $url           The feed’s URL.
	 * @param int    $limit         The maximum number of items to return. Default is 0 (no limit).
	 * @param int    $offset        The number of items to skip. Defaults to 0.
	 * @param string $cacheDuration Any valid [PHP time format](http://www.php.net/manual/en/datetime.formats.time.php).
	 *
	 * @return array|string The list of feed items.
	 */
	public function getFeedItems($url, $limit = null, $offset = null, $cacheDuration = null)
	{
		// Prevent $limit and $offset from being any empty value besides 0
		$limit = ($limit ?: 0);
		$offset = ($offset ?: 0);

		$items = [];

		if (!extension_loaded('dom'))
		{
			Craft::warning('Craft needs the PHP DOM extension (http://www.php.net/manual/en/book.dom.php) enabled to parse feeds.', __METHOD__);
			return $items;
		}

		if (!$cacheDuration)
		{
			$cacheDuration = Craft::$app->getConfig()->getCacheDuration();
		}
		else
		{
			$cacheDuration = DateTimeHelper::timeFormatToSeconds($cacheDuration);
		}

		// Potentially long-running request, so close session to prevent session blocking on subsequent requests.
		Craft::$app->getSession()->close();

		$feed = new \SimplePie();
		$feed->set_feed_url($url);
		$feed->set_cache_location(Craft::$app->getPath()->getCachePath());
		$feed->set_cache_duration($cacheDuration);
		$feed->init();

		// Something went wrong.
		if ($feed->error())
		{
			Craft::warning('There was a problem parsing the feed: '.$feed->error(), __METHOD__);
			return [];
		}

		foreach ($feed->get_items($offset, $limit) as $item)
		{
			// Validate the permalink
			$permalink = $item->get_permalink();

			if ($permalink)
			{
				$urlModel = new Url();
				$urlModel->url = $permalink;

				if (!$urlModel->validate())
				{
					Craft::log('An item was omitted from the feed ('.$url.') because its permalink was an invalid URL: '.$permalink);
					continue;
				}
			}

			$date = $item->get_date('U');
			$dateUpdated = $item->get_updated_date('U');

			$items[] = [
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
			];
		}

		return $items;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $objects
	 *
	 * @return array
	 */
	private function _getEnclosures($objects)
	{
		$enclosures = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$enclosures[] = [
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
				];
			}
		}

		return $enclosures;
	}

	/**
	 * @param $objects
	 *
	 * @return array
	 */
	private function _getRatings($objects)
	{
		$ratings = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$ratings[] = [
					'scheme'   => $object->get_scheme(),
					'value'  => $object->get_value(),
				];
			}
		}

		return $ratings;
	}

	/**
	 * @param $objects
	 *
	 * @return array
	 */
	private function _getRestrictions($objects)
	{
		$restrictions = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$restrictions[] = [
					'relationship' => $object->get_relationship(),
					'type'         => $object->get_type(),
					'value'        => $object->get_value(),
				];
			}
		}

		return $restrictions;
	}

	/**
	 * @param $objects
	 *
	 * @return array
	 */
	private function _getCaptions($objects)
	{
		$captions = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$captions[] = [
					'endtime'   => $object->get_endtime(),
					'language'  => $object->get_language(),
					'starttime' => $object->get_starttime(),
					'text'      => $object->get_text(),
					'type'      => $object->get_type(),
				];
			}
		}

		return $captions;
	}

	/**
	 * @param $objects
	 *
	 * @return array
	 */
	private function _getCredits($objects)
	{
		$credits = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$credits[] = [
					'role'   => $object->get_role(),
					'scheme' => $object->get_scheme(),
					'name'   => $object->get_name(),
				];
			}
		}

		return $credits;
	}

	/**
	 * @param $objects
	 *
	 * @return array
	 */
	private function _getCategories($objects)
	{
		$categories = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$categories[] = [
					'term'   => $object->get_term(),
					'scheme'  => $object->get_scheme(),
					'label' => $object->get_label(),
				];
			}
		}

		return $categories;
	}

	/**
	 * Returns an array of authors.
	 *
	 * @param array $objects
	 *
	 * @return array
	 */
	private function _getItemAuthors($objects)
	{
		$authors = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$authors[] = [
					'name'  => $object->get_name(),
					'url'   => $object->get_link(),
					'email' => $object->get_email(),
				];
			}
		}

		return $authors;
	}

	/**
	 * Returns an array of categories.
	 *
	 * @param array $objects
	 *
	 * @return array
	 */
	private function _getItemCategories($objects)
	{
		$categories = [];

		if ($objects)
		{
			foreach ($objects as $object)
			{
				$categories[] = [
					'term'   => $object->get_term(),
					'scheme' => $object->get_scheme(),
					'label'  => $object->get_label(),
				];
			}
		}

		return $categories;
	}
}
