<?php

namespace craft\elements\conditions\entries;

use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\conditions\HintableConditionRuleTrait;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Facades\Sections;
use function CraftCms\Cms\t;

/**
 * Entry section condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class SectionConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    use HintableConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected bool $reloadOnOperatorChange = true;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Section');
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
    protected function operators(): array
    {
        return [
            ...parent::operators(),
            self::OPERATOR_NOT_EMPTY,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return Sections::getAllSections()
            ->keyBy('uid')
            ->map(fn(Section $section) => $section->name . ($this->showLabelHint() ? " ($section->handle)" : ''))
            ->all();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            $query->section('*');
        } else {
            $query->sectionId($this->paramValue(fn($uid) => Sections::getSectionByUid($uid)->id ?? null));
        }
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            return $element->getSection() !== null;
        }

        return $this->matchValue($element->getSection()?->uid);
    }
}
