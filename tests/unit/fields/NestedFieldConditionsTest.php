<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Craft;
use craft\elements\conditions\entries\TypeConditionRule;
use craft\elements\conditions\users\AdminConditionRule;
use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;

/**
 * Tests that nested element field values submitted with their owner respect field layout conditions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class NestedFieldConditionsTest extends TestCase
{
    private CustomField $layoutElement;

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
        ];
    }

    protected function _before(): void
    {
        $user = new User([
            'username' => 'nested-' . StringHelper::randomString(6),
            'email' => 'nested-' . StringHelper::randomString(6) . '@example.com',
            'active' => true,
        ]);
        Craft::$app->getElements()->saveElement($user, false);
        Craft::$app->getUser()->setIdentity($user);

        $this->layoutElement = Craft::$app->getEntries()->getEntryTypeById(1008)
            ->getFieldLayout()
            ->getFieldByHandle('secondSubfield')
            ->layoutElement;
    }

    protected function _after(): void
    {
        $this->layoutElement->setEditCondition(null);
        $this->layoutElement->setElementEditCondition(null);
        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getGql()->setActiveSchema(null);
    }

    public function testEditableFieldsAreSet(): void
    {
        self::assertSame('Nested', $this->submitNestedValue());
    }

    public function testFieldsLockedByEditConditionsAreIgnored(): void
    {
        $condition = User::createCondition();
        $condition->addConditionRule(new AdminConditionRule(['value' => true]));
        $this->layoutElement->setEditCondition($condition);

        self::assertNull($this->submitNestedValue());
    }

    public function testFieldsLockedByElementEditConditionsAreIgnored(): void
    {
        $condition = Entry::createCondition();
        $condition->addConditionRule(new TypeConditionRule(['values' => ['entry-type-1009------------------uid']]));
        $this->layoutElement->setElementEditCondition($condition);

        self::assertNull($this->submitNestedValue());
    }

    public function testGraphqlTokenRequestsAreUnaffected(): void
    {
        $condition = User::createCondition();
        $condition->addConditionRule(new AdminConditionRule(['value' => true]));
        $this->layoutElement->setEditCondition($condition);

        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getGql()->setActiveSchema(new GqlSchema(['name' => 'Token', 'uid' => StringHelper::UUID()]));

        self::assertSame('Nested', $this->submitNestedValue());
    }

    private function submitNestedValue(): mixed
    {
        $entry = Entry::find()->section('withUri1')->title('With URL 1')->one();
        $entry->setFieldValueFromRequest('matrixSecond', [
            'sortOrder' => ['new:1'],
            'entries' => [
                'new:1' => [
                    'type' => 'matrixLayout2',
                    'fields' => ['secondSubfield' => 'Nested'],
                ],
            ],
        ]);

        $nestedEntries = $entry->getFieldValue('matrixSecond')->all();
        self::assertCount(1, $nestedEntries);
        return $nestedEntries[0]->getFieldValue('secondSubfield');
    }
}
