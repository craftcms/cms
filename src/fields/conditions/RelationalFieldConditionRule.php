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
use yii\db\QueryInterface;

/**
 * Relational field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RelationalFieldConditionRule extends BaseElementSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait {
        modifyQuery as traitModifyQuery;
    }

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
        if (!$this->field() instanceof BaseRelationField) {
            throw new InvalidConfigException();
        }

        return match ($this->operator) {
            self::OPERATOR_RELATED_TO => parent::inputHtml(),
            default => '',
        };
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $field = $this->field();
        if (!$field instanceof BaseRelationField) {
            return;
        }

        if ($this->operator === self::OPERATOR_RELATED_TO) {
            $this->traitModifyQuery($query);
        } else {
            // Add the condition manually so we can ignore the related elements’ statuses and the field’s target site
            // so conditions reflect what authors see in the UI
            $query->andWhere(
                $this->operator === self::OPERATOR_NOT_EMPTY
                    ? $field::existsQueryCondition($field, false, false)
                    : ['not', $field::existsQueryCondition($field, false, false)]
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): int|string|null
    {
        // $this->operator will always be OPERATOR_RELATED_TO at this point
        return $this->getElementId();
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof BaseRelationField) {
            return true;
        }

        if ($value instanceof ElementQueryInterface) {
            // Ignore the related elements’ statuses and target site
            // so conditions reflect what authors see in the UI
            $value = (clone $value)->site('*')->unique()->status(null);
        }

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
