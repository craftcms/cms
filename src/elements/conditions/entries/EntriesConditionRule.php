<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseElementsSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;

/**
 * Entries condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class EntriesConditionRule extends BaseElementsSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Entries');
    }

    /**
     * @inheritdoc
     */
    protected function elementType(): string
    {
        return Entry::class;
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $elementIds = $this->getElementIds();

        if ($this->operator === self::OPERATOR_NOT_IN) {
            ArrayHelper::prependOrAppend($elementIds, 'not', true);
        }
        /** @var EntryQuery $query */
        $query->id($elementIds);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue($element->id);
    }
}
