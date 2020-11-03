<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineGqlTypeFields class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class DefineGqlTypeFieldsEvent extends Event
{
    /**
     * @var array List of fields being defined for a GraphQL Type.
     */
    public $fields = [];

    /**
     * @var string The name of the GraphQL Type being defined.
     */
    public $typeName;
}
