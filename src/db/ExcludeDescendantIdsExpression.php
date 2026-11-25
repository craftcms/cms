<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Craft;

/**
 * ExcludeIdsExpression represents the condition SQL used to exclude certain
 * descendant element IDs from an element query
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.3
 */
class ExcludeDescendantIdsExpression implements ExpressionInterface
{
    /**
     * Constructor
     *
     * @param int[] $elementIds The element IDs to exclude
     */
    public function __construct(
        private readonly array $elementIds,
    ) {
    }

    /**
     * @param array $params
     * @return string
     */
    public function getSql(array &$params): string
    {
        return Craft::$app->getDb()->getQueryBuilder()->buildCondition(['not', ['elements.id' => $this->elementIds]], $params);
    }
}
