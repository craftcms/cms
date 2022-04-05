<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineConditionRuleTypesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RegisterConditionRuleTypesEvent extends Event
{
    /**
     * @var string[]|array[] The condition rules types.
     * @phpstan-var string[]|array{class:string}[]
     */
    public array $conditionRuleTypes = [];
}
