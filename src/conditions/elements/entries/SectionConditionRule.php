<?php

namespace craft\conditions\elements\entries;

use Craft;
use craft\conditions\BaseSelectConditionRule;
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
class SectionConditionRule extends BaseSelectConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Section');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['section', 'sectionId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $sections = Craft::$app->getSections()->getAllSections();
        return ArrayHelper::map($sections, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $section = Craft::$app->getSections()->getSectionByUid($this->value);

        if ($section) {
            /** @var EntryQuery $query */
            $query->section($section);
        }
    }
}
