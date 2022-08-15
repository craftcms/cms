<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\fields\BaseRelationField;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;

/**
 * Relational field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RelationalFieldConditionRule extends BaseElementSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    public const OPERATOR_RELATED_TO = 'relatedTo';

    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_RELATED_TO;

    /**
     * @inheritdoc
     */
    protected bool $reloadOnOperatorChange = true;

    /**
     * @inheritdoc
     */
    protected function elementType(): string
    {
        /** @var BaseRelationField $field */
        $field = $this->field();
        return $field::elementType();
    }

    /**
     * @inheritdoc
     */
    protected function sources(): ?array
    {
        /** @var BaseRelationField $field */
        $field = $this->field();
        return (array)$field->getInputSources();
    }

    /**
     * @inheritdoc
     */
    protected function selectionCondition(): ?ElementConditionInterface
    {
        /** @var BaseRelationField $field */
        $field = $this->field();
        return $field->getSelectionCondition();
    }

    /**
     * @inheritdoc
     */
    protected function criteria(): ?array
    {
        /** @var BaseRelationField $field */
        $field = $this->field();
        return $field->getInputSelectionCriteria();
    }

    /**
     * @inheritdoc
     */
    protected function operators(): array
    {
        return array_filter([
            self::OPERATOR_RELATED_TO,
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_RELATED_TO => Craft::t('app', 'is related to'),
            default => parent::operatorLabel($operator),
        };
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        return match ($this->operator) {
            self::OPERATOR_RELATED_TO => parent::inputHtml(),
            default => '',
        };
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): int|string|null
    {
        return match ($this->operator) {
            self::OPERATOR_RELATED_TO => $this->getElementId(),
            self::OPERATOR_EMPTY => ':empty:',
            self::OPERATOR_NOT_EMPTY => 'not :empty:',
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
        };
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($this->operator === self::OPERATOR_RELATED_TO) {
            $elementIds = $value->collect()->map(fn(ElementInterface $element) => $element->id)->all();
            return $this->matchValue($elementIds);
        }

        if ($value instanceof ElementQueryInterface) {
            $isEmpty = !$value->exists();
        } else {
            $isEmpty = $value->isEmpty();
        }

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $isEmpty;
        }

        return !$isEmpty;
    }
}
