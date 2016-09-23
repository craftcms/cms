<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\helpers\Number;
use craft\app\helpers\Template;

/**
 * Class Feeds variable.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class Feeds
{
    // Public Methods
    // =========================================================================

    /**
     * @param string  $url
     * @param integer $limit
     * @param integer $offset
     * @param null    $cacheDuration
     *
     * @return array
     */
    public function getFeedItems($url, $limit = 0, $offset = 0, $cacheDuration = null)
    {
        Craft::$app->getDeprecator()->log('craft.feeds.getFeedItems()', 'craft.feeds.getFeedItems() has been deprecated. Use craft.app.feeds.getFeedItems() instead.');

        $limit = Number::makeNumeric($limit);
        $offset = Number::makeNumeric($offset);
        $items = Craft::$app->getFeeds()->getFeedItems($url, $limit, $offset, $cacheDuration);

        // Prevent everyone from having to use the |raw filter when outputting the title and content
        $rawProperties = ['title', 'content', 'summary'];

        foreach ($items as &$item) {
            foreach ($rawProperties as $prop) {
                $item[$prop] = Template::getRaw($item[$prop]);
            }
        }

        return $items;
    }
}
