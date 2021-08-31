<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseSelectValueConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 *
 */
class EntrySectionConditionRule extends BaseSelectValueConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Section');
    }

    /**
     * @inheritDoc
     */
    public function getSelectOptions(): array
    {
        $sections = Craft::$app->getSections()->getAllSections();
        return ArrayHelper::map($sections, 'handle', 'name');
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        return $query->section($this->value);
    }
}