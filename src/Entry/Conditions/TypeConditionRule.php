<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\EntryTypes;
use Override;

use function CraftCms\Cms\t;

/**
 * Entry type condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class TypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Entry Type');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['type', 'typeId'];
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

    /** @param EntryQuery<Entry> $query */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->typeId($this->paramValue(fn ($uid) => EntryTypes::getEntryTypeByUid($uid)->id ?? null));
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue((string) $element->getType()->uid);
    }
}
