<?php

namespace craft\fields\conditions;

use Craft;
use craft\db\CoalesceColumnsExpression;
use craft\fields\data\LinkData;
use craft\fields\Link;
use craft\fields\linktypes\BaseLinkType;
use craft\helpers\Cp;
use yii\db\QueryInterface;

/**
 * Options field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class LinkFieldConditionRule extends TextFieldConditionRule
{
    private const OPERATOR_TYPE = 'type';

    /**
     * @var string|null The selected link type
     */
    public ?string $linkType = null;

    protected function operators(): array
    {
        return [
            ...parent::operators(),
            self::OPERATOR_TYPE,
        ];
    }

    public function getConfig(): array
    {
        return [
            ...parent::getConfig(),
            'linkType' => $this->linkType,
        ];
    }

    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_TYPE => Craft::t('app', 'is of type'),
            default => parent::operatorLabel($operator),
        };
    }

    protected function inputHtml(): string
    {
        if ($this->operator === self::OPERATOR_TYPE) {
            /** @var Link $field */
            $field = $this->field();
            $linkTypeOptions = array_map(
                fn(BaseLinkType $linkType) => ['value' => $linkType::id(), 'label' => $linkType::displayName()],
                $field->getLinkTypes(),
            );

            return Cp::selectHtml([
                'name' => 'linkType',
                'options' => $linkTypeOptions,
                'value' => $this->linkType,
            ]);
        }

        return parent::inputHtml();
    }

    public function modifyQuery(QueryInterface $query): void
    {
        if ($this->operator === self::OPERATOR_TYPE) {
            /** @phpstan-ignore-next-line */
            $valueSql = array_map(fn(Link $field) => $field->getValueSql('type'), $this->fieldInstances());

            $query->andWhere([
                (new CoalesceColumnsExpression($valueSql))->getSql($query->params) => $this->linkType,
            ]);
        } else {
            parent::modifyQuery($query);
        }
    }

    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof Link) {
            return true;
        }

        if ($this->operator === self::OPERATOR_TYPE) {
            /** @var LinkData|null $value */
            return $value?->getType() === $this->linkType;
        }

        return parent::matchFieldValue($value);
    }

    protected function defineRules(): array
    {
        return [
            ...parent::defineRules(),
            [['linkType'], 'safe'],
        ];
    }
}
