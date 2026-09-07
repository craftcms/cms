<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\EntryTypes;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

/**
 * Entry type condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class TypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof EntryCondition) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Entry Type');
    }

    #[Override]
    public function setAttributes($values, bool $safeOnly = true): void
    {
        if (array_key_exists('entryTypeUid', $values)) {
            $values['values'] = array_filter([$values['entryTypeUid']]);
            unset($values['entryTypeUid'], $values['sectionUid']);
        }

        parent::setAttributes($values);
    }

    protected function options(): array
    {
        return EntryTypes::getAllEntryTypes()
            ->mapWithKeys(fn (EntryType $entryType) => [$entryType->uid => $entryType->getUiLabel()])
            ->all();
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        EntryQuery::applyTypeId($query, $this->paramValue(fn ($uid) => EntryTypes::getEntryTypeByUid($uid)->id ?? null));
        ;
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue((string) $element->getType()->uid);
    }
}
