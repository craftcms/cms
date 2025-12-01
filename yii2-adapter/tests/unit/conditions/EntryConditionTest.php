<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\conditions;

use Codeception\Test\Unit;
use craft\elements\conditions\entries\ExpiryDateConditionRule;
use craft\elements\conditions\SlugConditionRule;
use craft\elements\Entry;
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
        $condition = Entry::createCondition();

        $rule1 = $condition->createConditionRule([
            'class' => SlugConditionRule::class,
        ]);
        $condition->addConditionRule($rule1);

        self::assertCount(1, $condition->getConditionRules());

        $rule1 = $condition->createConditionRule([
            'class' => ExpiryDateConditionRule::class,
        ]);
        $condition->addConditionRule($rule1);

        self::assertCount(2, $condition->getConditionRules());
    }
}
