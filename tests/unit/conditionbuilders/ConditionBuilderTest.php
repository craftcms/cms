<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\conditionbuilders;

use Codeception\Test\Unit;
use craft\conditions\BaseCondition;
use craft\base\TextConditionRule;


/**
 * Unit tests for conditions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
class ConditionBuilderTest extends Unit
{
    public function testConditionBuilderCanAddRules()
    {
        $conditionBuilder = new ElementQue();
        $rule = new TextConditionRule();
        $rule2 = new TextConditionRule();
        $conditionBuilder->addConditionRule($rule);
        $conditionBuilder->addConditionRule($rule2);
        self::assertCount(2, $conditionBuilder->getConditionRules()->all());
    }

    public function testConditionRuleCanAddRule()
    {
        $rule = new TextConditionRule();
        $rule->addConditionRule(new TextConditionRule());
        self::assertCount(0, $rule->getConditionRules()->all());

        $rule->allowChildRules = true;
        $rule->addConditionRule(new TextConditionRule());
        self::assertCount(0, $rule->getConditionRules()->all());

        $rule->maxChildRules = 3;
        $rule->minChildRules = 1;
        $rule->addConditionRule(new TextConditionRule());
        $rule->addConditionRule(new TextConditionRule());
        $rule->addConditionRule(new TextConditionRule());
        $rule->addConditionRule(new TextConditionRule()); // extra to test max is obeyed
        self::assertCount(3, $rule->getConditionRules()->all());
    }
}