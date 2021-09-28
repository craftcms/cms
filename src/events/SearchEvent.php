<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\db\ElementQuery;
use craft\search\SearchQuery;
use yii\base\Event as BaseEvent;

/**
 * SearchEvent class.
 *
 * @property int[]|null $elementIds
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SearchEvent extends BaseEvent
{
    /**
     * @var ElementQuery|null The element query being executed.
     * @since 3.7.14
     */
    public $elementQuery;

    /**
     * @var SearchQuery|null The search query
     */
    public $query;

    /**
     * @var int|int[]|null The site ID(s) to filter by
     */
    public $siteId;

    /**
     * @var array|null The raw search result data
     * @since 3.6.0
     */
    public $results;

    /**
     * @see getElementIds()
     * @see setElementIds()
     */
    private $_elementIds;

    /**
     * For [[\craft\services\Search::EVENT_BEFORE_SEARCH]], this will be the list of element IDs to filter
     * with the search query.
     *
     * For [[\craft\services\Search::EVENT_AFTER_SEARCH]], this will be the resulting list of element IDs that
     * match the search query.
     *
     * @return int[]|null
     */
    public function getElementIds(): ?array
    {
        if ($this->_elementIds === null && $this->elementQuery !== null) {
            $this->_elementIds = $this->elementQuery->ids();
        }
        return $this->_elementIds;
    }

    /**
     * @param int[]|null $elementIds
     * @since 3.7.14
     */
    public function setElementIds(?array $elementIds): void
    {
        $this->_elementIds = $elementIds;
    }
}
