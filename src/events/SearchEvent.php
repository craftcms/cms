<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\elements\db\ElementQuery;
use craft\search\SearchQuery;

/**
 * SearchEvent class.
 *
 * @property int[]|null $elementIds
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SearchEvent extends Event
{
    /**
     * @var ElementQuery The element query being executed.
     * @since 3.7.14
     */
    public ElementQuery $elementQuery;

    /**
     * @var SearchQuery The search query
     */
    public SearchQuery $query;

    /**
     * @var int|int[]|null The site ID(s) to filter by
     * @deprecated in 5.0.0. The `siteId` property on [[$elementQuery]] should be used instead.
     */
    public array|int|null $siteId = null;

    /**
     * @var array|null The raw search result data
     *
     * This will only be set ahead of time for [[\craft\services\Search::EVENT_BEFORE_SCORE_RESULTS]] and
     * [[\craft\services\Search::EVENT_AFTER_SEARCH]].
     *
     * If an event handler modifies this from [[\craft\services\Search::EVENT_BEFORE_SCORE_RESULTS]], then
     * [[\craft\services\Search::searchElements()]] will score the results set on the event rather than the original results.
     *
     * @since 3.6.0
     */
    public ?array $results = null;

    /**
     * @var array<string,int>|null The element scores indexed by element ID and site ID (e.g. `'100-1'`).
     *
     * This will only be set ahead of time for [[\craft\services\Search::EVENT_AFTER_SEARCH]].
     *
     * If an event handler sets this from [[\craft\services\Search::EVENT_BEFORE_SCORE_RESULTS]] or modifies it from
     * [[\craft\services\Search::EVENT_AFTER_SEARCH]], then [[\craft\services\Search::searchElements()]] will return its
     * value rather than calculate the result scores itself.
     *
     * @since 4.3.0
     */
    public ?array $scores = null;
}
