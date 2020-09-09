<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\Number;
use craft\helpers\Template;

/**
 * Class Feeds variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class Feeds
{
    /**
     * @param string $url
     * @param int $limit
     * @param int $offset
     * @param string|null $cacheDuration
     * @return array
     */
    public function getFeedItems(string $url, int $limit = 0, int $offset = 0, string $cacheDuration = null): array
    {
        Craft::$app->getDeprecator()->log('craft.feeds.getFeedItems()', '`craft.feeds.getFeedItems()` has been deprecated.');

        $limit = Number::makeNumeric($limit);
        $offset = Number::makeNumeric($offset);
        $items = Craft::$app->getFeeds()->getFeedItems($url, $limit, $offset, $cacheDuration);

        // Prevent everyone from having to use the |raw filter when outputting the title and content
        $rawProperties = ['title', 'content', 'summary'];

        foreach ($items as &$item) {
            foreach ($rawProperties as $prop) {
                $item[$prop] = Template::raw($item[$prop]);
            }
        }

        return $items;
    }
}
