<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\search\SearchQuery;
use yii\base\Event;

/**
 * SearchEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SearchEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var integer[] The list of element IDs to filter by the search query, or the filtered list of element IDs,
     *                depending on if this is a beforeSearch or afterSearch event
     */
    public $elementIds = true;

    /**
     * @var SearchQuery The search query
     */
    public $query;

    /**
     * @var integer The site ID to filter by
     */
    public $siteId;
}
