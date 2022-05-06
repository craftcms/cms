<?php

namespace craft\migrations;

use Craft;
use craft\base\conditions\BaseConditionRule;
use craft\base\Field;
use craft\db\Migration;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\conditions\entries\TypeConditionRule;
use craft\fields\Entries;
use craft\helpers\ArrayHelper;

/**
 * m220506_000000_update_entry_type_conditions.
 */
class m220506_000000_update_entry_type_conditions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migrateFields = ArrayHelper::where(Craft::$app->getFields()->getAllFields(), static function(Field $field) {
            if (!$field instanceof Entries) {
                return false;
            }

            $selectionCondition = $field->getSelectionCondition();
            if (!$selectionCondition instanceof EntryCondition) {
                return false;
            }

            /** @var BaseConditionRule[]|null $conditionRules */
            $conditionRules = $selectionCondition->getConditionRules();
            if (empty($conditionRules)) {
                return false;
            }

            $entryTypeConditionRules = array_filter($conditionRules, static fn($conditionRules) => $conditionRules instanceof TypeConditionRule);
            if (empty($entryTypeConditionRules)) {
                return false;
            }

            /** @var TypeConditionRule $entryTypeConditionRule */
            $entryTypeConditionRule = array_shift($entryTypeConditionRules);
            if (!$entryTypeConditionRule->entryTypeUid) {
                return false;
            }

            return true;
        });

        /** @var Entries[]|null $migrateFields */
        if (empty($migrateFields)) {
            return true;
        }

        foreach ($migrateFields as $migrateField) {
            foreach ($migrateField->getSelectionCondition()->getConditionRules() as $conditionRule) {
                if (!$conditionRule instanceof TypeConditionRule) {
                    continue;
                }

                $conditionRule->setValues([$conditionRule->entryTypeUid]);
                $conditionRule->entryTypeUid = null;

                Craft::$app->getFields()->saveField($migrateField, false);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220506_000000_update_entry_type_conditions cannot be reverted.\n";
        return false;
    }
}
