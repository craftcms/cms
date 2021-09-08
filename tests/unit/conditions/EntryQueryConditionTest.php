<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\conditions;

use Codeception\Test\Unit;
use craft\conditions\elements\entry\AuthorGroupConditionRule;
use craft\conditions\elements\entry\EntryQueryCondition;
use craft\conditions\elements\entry\Slug;


/**
 * Unit tests for entry query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class EntryQueryConditionTest extends Unit
{
    public function testCanAddRules()
    {
        $config = [
            'type' => EntryQueryCondition::class,
        ];
        /** @var EntryQueryCondition $condition */
        $condition = \Craft::$app->getConditions()->createCondition($config);

        $ruleConfig = [
            'type' => Slug::class,
        ];
        $rule1 = \Craft::$app->getConditions()->createConditionRule($ruleConfig);
        $condition->addConditionRule($rule1);

        self::assertCount(1, $condition->getConditionRules()->all());

        $ruleConfig2 = [
            'type' => AuthorGroupConditionRule::class,
        ];
        $rule1 = \Craft::$app->getConditions()->createConditionRule($ruleConfig2);
        $condition->addConditionRule($rule1);

        self::assertCount(2, $condition->getConditionRules()->all());
    }
}