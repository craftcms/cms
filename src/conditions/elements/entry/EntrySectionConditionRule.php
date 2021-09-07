<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseSelectValueConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Entry section condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class EntrySectionConditionRule extends BaseSelectValueConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Section');
    }

    /**
     * @inheritdoc
     */
    public function getSelectOptions(): array
    {
        $sections = Craft::$app->getSections()->getAllSections();
        return ArrayHelper::map($sections, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        $sectionService = Craft::$app->getSections();
        $section = $sectionService->getSectionByUid($this->value);

        /** @var EntryQuery $query */
        return $query->section($section);
    }
}
