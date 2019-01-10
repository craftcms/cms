<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\feeds;

use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\ConfigHelper;
use craft\models\Url;
use yii\base\Component;
use yii\base\InvalidConfigException;
use Zend\Feed\Reader\Entry\EntryInterface;
use Zend\Feed\Reader\Exception\RuntimeException;
use Zend\Feed\Reader\Feed\FeedInterface;
use Zend\Feed\Reader\Reader;

/**
 * The Feeds service provides APIs for fetching remote RSS and Atom feeds.
 * An instance of the Feeds service is globally accessible in Craft via [[\craft\web\Application::feeds|`Craft::$app->feeds`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Feeds extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Fetches and parses an RSS or Atom feed, and returns info about the feed and its items.
     *
     * The returned array will have the following keys:
     *
     * - `authors` – An array of the feed’s authors, where each sub-element has the following keys:
     *     - `name` – The author’s name
     *     - `url` – The author’s URL
     *     - `email` – The author’s email
     * - `categories` – An array of the feed’s categories, where each sub-element has the following keys:
     *     - `term` – The category’s term
     *     - `scheme` – The category’s scheme
     *     - `label` – The category’s label
     * - `copyright` – The copyright info for the feed, or null uf not known.
     * - `dateCreated` – The feed’s creation date, or null if not known.
     * - `dateUpdated` – The feed’s last modification date, or null if not known.
     * - `description` – The feed’s description, or null if not known.
     * - `generator` – The feed’s generator, or null if not known.
     * - `id` – The feed’s ID, or null if not known.
     * - `items` – An array of the feed’s items. See [[getFeedItems()]] for a
     *   list of keys each element in this array will contain.
     * - `language` – The feed’s language, or null if not known.
     * - `link` – The link to the feed’s HTML source, or null if not known.
     * - `title` – The feed’s title, or null if not known.
     *
     * ---
     *
     * ```php
     * $feedUrl = 'https://craftcms.com/news.rss';
     * $feed = Craft::$app->feeds->getFeed($feedUrl, 10);
     * ```
     * ```twig
     * {% set feedUrl = "https://craftcms.com/news.rss" %}
     * {% set feed = craft.app.feeds.getFeed(feedUrl) %}
     *
     * <h3>{{ feed.title }}</h3>
     *
     * {% for item in feed.items[0:10] %}
     *     <article>
     *         <h3><a href="{{ item.permalink }}">{{ item.title }}</a></h3>
     *         <p class="author">{{ item.authors[0].name }}</p>
     *         <p class="date">{{ item.date|date('short') }}</p>
     *         {{ item.summary }}
     *     </article>
     * {% endfor %}
     * ```
     *
     * @param string $url The feed’s URL.
     * @param mixed|null $cacheDuration How long to cache the results. See [[Config::timeInSeconds()]] for possible values.
     * @return array The feed info
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    public function getFeed(string $url, string $cacheDuration = null): array
    {
        // Key based on the classname, url, limit and offset.
        $key = md5(self::class . '.' . $url);

        // See if we have this cached already.
        if (($cached = Craft::$app->getCache()->get($key)) !== false) {
            return $cached;
        }

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            // Potentially long-running request, so close session to prevent session blocking on subsequent requests.
            Craft::$app->getSession()->close();
        }

        Reader::setHttpClient(new GuzzleClient());

        try {
            $feed = Reader::import($url);
        } catch (RuntimeException $e) {
            Craft::warning('There was a problem parsing the feed: ' . $e->getMessage(), __METHOD__);
            return [];
        }

        $timezone = new \DateTimeZone(Craft::$app->getTimeZone());
        $dateCreated = $feed->getDateCreated();
        $dateUpdated = $feed->getDateModified();

        $info = [
            'authors' => $this->_getItemAuthors($feed->getAuthors()),
            'categories' => $this->_getItemCategories($feed->getCategories()),
            'copyright' => $feed->getCopyright(),
            'dateCreated' => $dateCreated ? $dateCreated->setTimezone($timezone) : null,
            'dateUpdated' => $dateUpdated ? $dateUpdated->setTimezone($timezone) : null,
            'description' => $feed->getDescription(),
            'generator' => $feed->getGenerator(),
            'id' => $feed->getId(),
            'items' => $this->_getFeedItems($feed),
            'language' => $feed->getLanguage(),
            'link' => $feed->getLink(),
            'title' => $feed->getTitle(),
        ];

        // Normalize the cache duration
        if ($cacheDuration !== null) {
            $cacheDuration = ConfigHelper::durationInSeconds($cacheDuration);
        }

        Craft::$app->getCache()->set($key, $info, $cacheDuration);

        return $info;
    }

    /**
     * Fetches and parses an RSS or Atom feed, and returns its items.
     *
     * Each element in the returned array will have the following keys:
     * - `authors` – An array of the item’s authors, where each sub-element has the following keys:
     *     - `name` – The author’s name
     *     - `url` – The author’s URL
     *     - `email` – The author’s email
     * - `categories` – An array of the item’s categories, where each sub-element has the following keys:
     *     - `term` – The category’s term
     *     - `scheme` – The category’s scheme
     *     - `label` – The category’s label
     * - `content` – The item’s main content.
     * - `contributors` – An array of the item’s contributors, where each sub-element has the following keys:
     *     - `name` – The contributor’s name
     *     - `url` – The contributor’s URL
     *     - `email` – The contributor’s email
     * - `date` – A [[DateTime]] object representing the item’s date.
     * - `dateUpdated` – A [[DateTime]] object representing the item’s last updated date.
     * - `permalink` – The item’s URL.
     * - `summary` – The item’s summary content.
     * - `title` – The item’s title.
     *
     * ---
     *
     * ```php
     * $feedUrl = 'https://craftcms.com/news.rss';
     * $items = Craft::$app->feeds->getFeedItems($feedUrl, 10);
     * ```
     * ```twig
     * {% set feedUrl = "https://craftcms.com/news.rss" %}
     * {% set items = craft.app.feeds.getFeedItems(feedUrl, 10) %}
     *
     * {% for item in items %}
     *     <article>
     *         <h3><a href="{{ item.permalink }}">{{ item.title }}</a></h3>
     *         <p class="author">{{ item.authors[0].name }}</p>
     *         <p class="date">{{ item.date|date('short') }}</p>
     *         {{ item.summary }}
     *     </article>
     * {% endfor %}
     * ```
     *
     * @param string $url The feed’s URL.
     * @param int|null $limit The maximum number of items to return. Default is 0 (no limit).
     * @param int|null $offset The number of items to skip. Defaults to 0.
     * @param mixed|null $cacheDuration How long to cache the results. See [[Config::timeInSeconds()]] for possible values.
     * @return array The list of feed items.
     * @throws InvalidConfigException
     * @throws MissingComponentException
     */
    public function getFeedItems(string $url, int $limit = null, int $offset = null, string $cacheDuration = null): array
    {
        $items = $this->getFeed($url, $cacheDuration)['items'];

        if ($limit === 0) {
            $items = array_slice($items, $offset);
        } else {
            $items = array_slice($items, $offset, $limit);
        }

        return $items;
    }

    // Private Methods
    // =========================================================================\

    /**
     * Returns an array of a feed’s items.
     *
     * @param FeedInterface $feed
     * @return array
     */
    private function _getFeedItems(FeedInterface $feed): array
    {
        $items = [];
        $timezone = new \DateTimeZone(Craft::$app->getTimeZone());

        foreach ($feed as $item) {
            /** @var EntryInterface $item */
            // Validate the permalink
            $permalink = $item->getPermalink();

            if ($permalink) {
                $urlModel = new Url();
                $urlModel->url = $permalink;

                if (!$urlModel->validate()) {
                    Craft::info('An item was omitted from the feed (' . $feed->getFeedLink() . ') because its permalink was an invalid URL: ' . $permalink, __METHOD__);
                    continue;
                }
            }

            $date = $item->getDateCreated();
            $dateUpdated = $item->getDateModified();

            $items[] = [
                'authors' => $this->_getItemAuthors($item->getAuthors()),
                'categories' => $this->_getItemCategories($item->getCategories()),
                'content' => $item->getContent(),
                // See: https://github.com/zendframework/zendframework/issues/2969
                // and https://github.com/zendframework/zendframework/pull/3570
                'contributors' => $this->_getItemAuthors($item->getAuthors()),
                'date' => $date ? $date->setTimezone($timezone) : null,
                'dateUpdated' => $dateUpdated ? $dateUpdated->setTimezone($timezone) : null,
                'permalink' => $item->getPermalink(),
                'summary' => $item->getDescription(),
                'title' => $item->getTitle(),
                'enclosures' => $item->getEnclosure(),
            ];
        }

        return $items;
    }

    /**
     * Returns an array of authors.
     *
     * @param \stdClass[] $objects
     * @return array
     */
    private function _getItemAuthors($objects): array
    {
        $authors = [];

        if (!empty($objects)) {
            foreach ($objects as $object) {
                $authors[] = [
                    'name' => $object['name'] ?? '',
                    'url' => $object['uri'] ?? '',
                    'email' => $object['email'] ?? '',
                ];
            }
        }

        return $authors;
    }

    /**
     * Returns an array of categories.
     *
     * @param mixed $objects
     * @return array
     */
    private function _getItemCategories($objects): array
    {
        $categories = [];

        if (!empty($objects)) {
            foreach ($objects as $object) {
                $categories[] = [
                    'term' => $object['term'] ?? '',
                    'scheme' => $object['scheme'] ?? '',
                    'label' => $object['label'] ?? '',
                ];
            }
        }

        return $categories;
    }
}
