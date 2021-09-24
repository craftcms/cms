<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseSelectOperatorConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Entry section condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class SectionConditionRule extends BaseSelectOperatorConditionRule implements QueryConditionRuleInterface
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
    public static function queryParams(): array
    {
        return ['section', 'sectionId'];
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
        /** @var EntryQuery $query */
        $section = Craft::$app->getSections()->getSectionByUid($this->optionValue);

        if ($section) {
            $query->section($section);
        }
    }
}
