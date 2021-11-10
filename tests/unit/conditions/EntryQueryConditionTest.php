<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\conditions;

use Codeception\Test\Unit;
use Craft;
use craft\conditions\elements\entry\AuthorGroupConditionRule;
use craft\conditions\elements\entry\EntryQueryCondition;
use craft\conditions\elements\SlugConditionRule;


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
            'class' => EntryQueryCondition::class,
        ];
        /** @var EntryQueryCondition $condition */
        $condition = Craft::$app->getConditions()->createCondition($config);

        $ruleConfig = [
            'class' => SlugConditionRule::class,
        ];
        $rule1 = Craft::$app->getConditions()->createConditionRule($ruleConfig);
        $condition->addConditionRule($rule1);

        self::assertCount(1, $condition->getConditionRules());

        $ruleConfig2 = [
            'class' => AuthorGroupConditionRule::class,
        ];
        $rule1 = Craft::$app->getConditions()->createConditionRule($ruleConfig2);
        $condition->addConditionRule($rule1);

        self::assertCount(2, $condition->getConditionRules());
    }
}
