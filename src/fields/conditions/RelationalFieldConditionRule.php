<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\fields\BaseRelationField;
use yii\base\InvalidConfigException;

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
    protected function allowMultiple(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_RELATED_TO,
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ];
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
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $field = $this->field();
        if (!$field instanceof BaseRelationField) {
            return;
        }

        // If this is one of multiple instances of the relation field in the layout,
        // look at the JSON values rather than the `relations` table data
        // (see https://github.com/craftcms/cms/issues/17290)
        $allInstances = $field->layoutElement?->getLayout()->getFields(fn(BaseField $field) => (
            $field instanceof CustomField &&
            $field->getFieldUid() === $this->_fieldUid
        ));
        if ($allInstances && count($allInstances) > 1) {
            $valueSql = $field->getValueSql();
            switch ($this->operator) {
                case self::OPERATOR_RELATED_TO:
                    $qb = Craft::$app->getDb()->getQueryBuilder();
                    $query->andWhere([
                        'or',
                        ...array_map(fn(int $id) => $qb->jsonContains($valueSql, $id), $this->getElementIds()),
                    ]);
                    break;
                case self::OPERATOR_NOT_EMPTY:
                    $query->andWhere(
                        [
                            'and',
                            ['not', [$valueSql => null]],
                            ['not', [$valueSql => '[]']],
                        ]
                    );
                    break;
                case self::OPERATOR_EMPTY:
                    $query->andWhere(
                        [
                            'or',
                            [$valueSql => null],
                            [$valueSql => '[]'],
                        ]
                    );
                    break;
            }
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
    protected function elementQueryParam(): array|null
    {
        // $this->operator will always be OPERATOR_RELATED_TO at this point
        return $this->getElementIds();
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

        /** @var ElementQueryInterface|ElementCollection $value */
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
