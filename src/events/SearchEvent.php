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
     */
    public array|int|null $siteId = null;

    /**
     * @var array|null The raw search result data
     * @since 3.6.0
     */
    public ?array $results = null;
}
