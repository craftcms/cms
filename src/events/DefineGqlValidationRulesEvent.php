<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineGqlValidationRulesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class DefineGqlValidationRulesEvent extends Event
{
    /**
     * @var array List of GraphQL validation rules to use.
     */
    public $validationRules = [];

    /**
     * @var array Whether debug rules should be allowed
     */
    public $debug = false;
}
