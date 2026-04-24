<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\HintableConditionRuleTrait;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Facades\Sections;
use Override;

use function CraftCms\Cms\t;

/**
 * Entry section condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class SectionConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    use HintableConditionRuleTrait;

    #[Override]
    protected bool $reloadOnOperatorChange = true;

    public function getLabel(): string
    {
        return t('Section');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['section', 'sectionId'];
    }

    #[Override]
    protected function operators(): array
    {
        return [
            ...parent::operators(),
            self::OPERATOR_NOT_EMPTY,
        ];
    }

    protected function options(): array
    {
        return Sections::getAllSections()
            ->keyBy('uid')
            ->map(fn (Section $section) => $section->name.($this->showLabelHint() ? " ($section->handle)" : ''))
            ->all();
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            $query->section('*');
        } else {
            $query->sectionId($this->paramValue(fn ($uid) => Sections::getSectionByUid($uid)->id ?? null));
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        if ($this->operator === self::OPERATOR_NOT_EMPTY) {
            return $element->getSection() !== null;
        }

        return $this->matchValue($element->getSection()?->uid);
    }
}
