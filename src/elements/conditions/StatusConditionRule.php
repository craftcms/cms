<?php

namespace craft\elements\conditions;

use Craft;
use craft\base\conditions\BaseSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use yii\db\QueryInterface;

/**
 * Element status condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class StatusConditionRule extends BaseSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Status');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['status'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        /** @var ElementCondition $condition */
        $condition = $this->getCondition();
        /** @var ElementInterface|string $elementType */
        $elementType = $condition->elementType;
        return array_map(fn($info) => $info['label'] ?? $info, $elementType::statuses());
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->status($this->value);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getStatus());
    }
}
