<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

/**
 * Entry section type condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class SectionTypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof EntryCondition) {
            return false;
        }

        // Exclude from section sources
        if (isset($condition->sourceKey) && str_starts_with($condition->sourceKey, 'section:')) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Section Type');
    }

    protected function options(): array
    {
        return collect(SectionType::cases())
            ->mapWithKeys(fn (SectionType $type) => [$type->value => $type->label()])
            ->all();
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        if ($this->getValues() === []) {
            return;
        }

        $sectionIds = Sections::getAllSections()
            ->filter(fn (Section $section) => $this->matchValue($section->type->value))
            ->map(fn (Section $section) => $section->id)
            ->values()
            ->all();

        EntryQuery::applySectionId($query, $sectionIds, $elementQuery);
    }

    public function matchElement(ElementInterface $element): bool
    {
        if ($this->getValues() === []) {
            return true;
        }

        /** @var Entry $element */
        $section = $element->getSection();

        // The query only lists entries in sections, so sectionless entries, such as nested ones, don't match.
        return $section !== null && $this->matchValue($section->type->value);
    }
}
