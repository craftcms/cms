<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterConditionRulesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class RegisterConditionRulesEvent extends Event
{
    /**
     * @var string[]|array[] The condition rules types.
     * @phpstan-var string[]|array{class:string}[]
     */
    public array $conditionRules = [];
}
