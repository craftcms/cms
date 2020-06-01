<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\queryrules;

use craft\elements\db\ElementQuery;
use yii\base\BaseObject;

/**
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5
 */
abstract class ElementUId extends BaseObject
{

    public $type = 'numeric';
    public $id = 'elementId';

    public function modifyQuery(ElementQuery $query, $queryData): ElementQuery
    {
        if($queryData) // contains a id rules in the query?
        {
            // array of IDs? single ID?
            return $query->id($queryData['value']);
        }

        return $query;
    }

    /**
     * @return array
     */
    public function getVueComponent()
    {
        // get base element conditions/rules
        // get custom field conditions/rules
        // extending class will add additional conditions
        return [];
    }
}