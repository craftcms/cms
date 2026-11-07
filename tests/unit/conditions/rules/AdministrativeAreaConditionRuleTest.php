<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\conditions\rules;

use Craft;
use craft\elements\Address;
use craft\elements\conditions\addresses\AdministrativeAreaConditionRule;
use craft\test\TestCase;

/**
 * Unit tests for AdministrativeAreaConditionRule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class AdministrativeAreaConditionRuleTest extends TestCase
{
    public function testLegacyGbCountyNames(): void
    {
        /** @var AdministrativeAreaConditionRule $rule */
        $rule = Craft::$app->getConditions()->createConditionRule([
            'class' => AdministrativeAreaConditionRule::class,
            'condition' => Address::createCondition(),
            'operator' => 'in',
            'values' => ['Surrey', 'Durham, County', 'Custom'],
            'countryCode' => 'GB',
        ]);
        self::assertSame(['SRY', 'DUR', 'Custom'], $rule->getValues());
        self::assertTrue($rule->matchElement(new Address(['countryCode' => 'GB', 'administrativeArea' => 'SRY'])));
        // selected codes keep their labels
        self::assertStringContainsString('>Surrey<', $rule->getHtml());

        // other countries' values are left alone
        /** @var AdministrativeAreaConditionRule $rule */
        $rule = Craft::$app->getConditions()->createConditionRule([
            'class' => AdministrativeAreaConditionRule::class,
            'condition' => Address::createCondition(),
            'values' => ['Surrey'],
            'countryCode' => 'US',
        ]);
        self::assertSame(['Surrey'], $rule->getValues());
    }
}
