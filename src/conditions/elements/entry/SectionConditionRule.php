<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseSelectOperatorConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Entry section condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class SectionConditionRule extends BaseSelectOperatorConditionRule implements ElementQueryConditionRuleInterface
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
    public function modifyQuery(QueryInterface $query): void
    {
        $sectionService = Craft::$app->getSections();
        $section = $sectionService->getSectionByUid($this->value);

        $query->section($section);
    }
}
