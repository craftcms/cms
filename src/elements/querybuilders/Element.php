<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\querybuilders;

use craft\elements\db\ElementQuery;
use craft\elements\queryrules\ElementId;
use yii\base\BaseObject;

/**
 *
 * @property array $rules
 */
abstract class Element extends BaseObject
{
    /**
     * @var ElementQuery
     */
    private $_queryClass;

    public $maxDepth = 1;

    /**
     * @return array
     */
    public function getRules()
    {
        // get base element conditions/rules
        // get custom field conditions/rules
        // extending class will add additional conditions
        return [
            ElementId::class
        ];
    }
}