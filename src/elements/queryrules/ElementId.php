<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\queryrules;

use craft\elements\db\ElementQuery;

/**
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5
 */
abstract class ElementId extends BaseRule
{
    public function modifyQuery(ElementQuery $query, $queryData): ElementQuery
    {
        if ($queryData) // contains a id rules in the query?
        {
            // array of IDs? single ID?
            return $query->uid($queryData['value']);
        }

        return $query;
    }

    public function getLabel()
    {
        return "ID";
    }

    /**
     * Unique ID for the rules
     */
    public function getId()
    {
        return 'elementId';
    }
}