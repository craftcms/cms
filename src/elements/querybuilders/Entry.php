<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\querybuilders;

use craft\elements\db\ElementQuery;
use yii\base\BaseObject;

/**
 *
 * @property array $rules
 */
class Entry extends Element
{
    /**
     * @var ElementQuery
     */
    private $_queryClass;

    /**
     * @return array
     */
    public function getRules()
    {
        // get base element conditions/rules
        // get custom field conditions/rules
        // extending class will add additional conditions
        return parent::getRules();
    }
}