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

    const OPERATOR_RELATED_TO = 'relatedTo';

    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_NOT_EMPTY;

    /**
     * @inheritdoc
     */
    protected bool $reloadOnOperatorChange = true;

    /**
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return true;
    }

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
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
            !$this->getCondition()->forProjectConfig ? self::OPERATOR_RELATED_TO : null,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function operatorLabel(string $operator): string
    {
        switch ($operator) {
            case self::OPERATOR_RELATED_TO:
                return Craft::t('app', 'is related to');
            default:
                return parent::operatorLabel($operator);
        }
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        switch ($this->operator) {
            case self::OPERATOR_RELATED_TO:
                return parent::inputHtml();
            default:
                return '';
        }
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam()
    {
        switch ($this->operator) {
            case self::OPERATOR_RELATED_TO:
                return $this->getElementId();
            case self::OPERATOR_EMPTY:
                return ':empty:';
            case self::OPERATOR_NOT_EMPTY:
                return 'not :empty:';
            default:
                throw new InvalidConfigException("Invalid operator: $this->operator");
        }
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($this->operator === self::OPERATOR_RELATED_TO) {
            $elementIds = $value->collect()->map(fn(ElementInterface $element) => $element->id);
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
