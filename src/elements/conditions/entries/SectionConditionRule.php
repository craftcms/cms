<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;

/**
 * Entry section condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class SectionConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
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
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $sections = Craft::$app->getSections();
        $query->sectionId($this->paramValue(fn($uid) => $sections->getSectionByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue($element->getSection()->uid);
    }
}
