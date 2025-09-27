<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;

/**
 * Entry type condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Entry Type');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['type', 'typeId'];
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        if (array_key_exists('entryTypeUid', $values)) {
            $values['values'] = array_filter([$values['entryTypeUid']]);
            unset($values['entryTypeUid'], $values['sectionUid']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @return array
     */
    protected function options(): array
    {
        $options = [];
        foreach (Craft::$app->getEntries()->getAllEntryTypes() as $entryType) {
            $options[$entryType->uid] = $entryType->getUiLabel();
        }
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $entriesService = Craft::$app->getEntries();
        $query->typeId($this->paramValue(fn($uid) => $entriesService->getEntryTypeByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue((string)$element->getType()->uid);
    }
}
