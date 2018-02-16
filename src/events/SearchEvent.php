<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\search\SearchQuery;
use yii\base\Event as BaseEvent;

/**
 * SearchEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SearchEvent extends BaseEvent
{
    // Properties
    // =========================================================================

    /**
     * @var int[] The list of element IDs to filter by the search query, or the
     * filtered list of element IDs, depending on if this is a beforeSearch or
     * `afterSearch` event
     */
    public $elementIds = true;

    /**
     * @var SearchQuery|null The search query
     */
    public $query;

    /**
     * @var int|null The site ID to filter by
     */
    public $siteId;
}
