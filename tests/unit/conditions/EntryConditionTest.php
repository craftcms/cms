<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\conditions;

use Codeception\Test\Unit;
use Craft;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\conditions\entries\ExpiryDateConditionRule;
use craft\elements\conditions\SlugConditionRule;
use craft\test\TestCase;

/**
 * Unit tests for entry conditions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class EntryConditionTest extends TestCase
{
    public function testCanAddRules(): void
    {
        $config = [
            'class' => EntryCondition::class,
        ];
        /** @var EntryCondition $condition */
        $condition = Craft::$app->getConditions()->createCondition($config);

        $ruleConfig = [
            'class' => SlugConditionRule::class,
        ];
        $rule1 = Craft::$app->getConditions()->createConditionRule($ruleConfig);
        $condition->addConditionRule($rule1);

        self::assertCount(1, $condition->getConditionRules());

        $ruleConfig2 = [
            'class' => ExpiryDateConditionRule::class,
        ];
        $rule1 = Craft::$app->getConditions()->createConditionRule($ruleConfig2);
        $condition->addConditionRule($rule1);

        self::assertCount(2, $condition->getConditionRules());
    }
}
