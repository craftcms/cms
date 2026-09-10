<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Money as MoneyControl;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Money as MoneyHelper;
use Money\Money as MoneyLibrary;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

class MoneyFieldConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface, FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    public function __construct(array|object $config = [])
    {
        $config = (array) $config;
        $moneyValues = array_filter(Arr::only($config, ['value', 'maxValue']), is_array(...));

        parent::__construct(Arr::except($config, array_keys($moneyValues)));

        if ($moneyValues !== []) {
            $this->setAttributes($moneyValues);
        }
    }

    /** @return list<string> */
    #[Override]
    protected function operators(): array
    {
        return array_filter(
            parent::operators(),
            // Remove IN/NOT IN operators as they don't fit with the implementation of money inputs
            fn (string $operator) => ! in_array($operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  bool  $safeOnly
     */
    #[Override]
    public function setAttributes($values, $safeOnly = true): void
    {
        // Hold setting of the value attribute until we have all the info we need
        if (isset($values['value']) && is_array($values['value'])) {
            $value = Arr::pull($values, 'value');
        }

        if (isset($values['maxValue']) && is_array($values['maxValue'])) {
            $maxValue = Arr::pull($values, 'maxValue');
        }

        parent::setAttributes($values);

        $field = $this->field();

        if (! $field instanceof Money) {
            throw new RuntimeException;
        }

        if (isset($value, $this->_fieldUid)) {
            $value['currency'] ??= $field->currency;

            $this->value = MoneyHelper::toDecimal(MoneyHelper::toMoney($value));
        }

        if (isset($maxValue, $this->_fieldUid)) {
            $maxValue['currency'] ??= $field->currency;
            $this->maxValue = MoneyHelper::toDecimal(MoneyHelper::toMoney($maxValue));
        }
    }

    /** @return list<Node> */
    #[Override]
    protected function inputNodes(): array
    {
        $field = $this->field();

        if (! $field instanceof Money) {
            throw new RuntimeException;
        }

        if (in_array($this->operator, [self::OPERATOR_EMPTY, self::OPERATOR_NOT_EMPTY], true)) {
            return [];
        }

        $control = fn (string $path, string $value): MoneyControl => MoneyControl::make($path)
            ->currency($field->currency)
            ->showCurrency($field->showCurrency)
            ->value(is_numeric($value)
                ? MoneyHelper::toNumber(MoneyHelper::toMoney(['value' => $value, 'currency' => $field->currency]))
                : $value);

        if ($this->operator === self::OPERATOR_BETWEEN) {
            return [
                Field::make(t('Min Value'), $control('value', $this->value)),
                Field::make(t('Max Value'), $control('maxValue', $this->maxValue))
                    ->tip(t('The values are matched inclusively.')),
            ];
        }

        return [Field::make($this->getLabel(), $control('value', $this->value))];
    }

    protected function elementQueryParam(): ?string
    {
        if (! $this->field() instanceof Money) {
            return null;
        }

        return $this->paramValue();
    }

    /** @param MoneyLibrary|float|int|null $value */
    protected function matchFieldValue(mixed $value): bool
    {
        if (! $this->field() instanceof Money) {
            return true;
        }

        if (! $value instanceof MoneyLibrary || in_array($this->operator, [self::OPERATOR_EMPTY, self::OPERATOR_NOT_EMPTY], true)) {
            return $this->matchValue($value instanceof MoneyLibrary ? $value->getAmount() : $value);
        }

        $compare = fn (string $amount): int => $value->compare(MoneyHelper::toMoney([
            'value' => $amount,
            'currency' => $value->getCurrency(),
        ]));

        if ($this->operator === self::OPERATOR_BETWEEN) {
            return ($this->value === '' || $compare($this->value) >= 0)
                && ($this->maxValue === '' || $compare($this->maxValue) <= 0);
        }

        if ($this->value === '') {
            return true;
        }

        $comparison = $compare($this->value);

        return match ($this->operator) {
            self::OPERATOR_EQ => $comparison === 0,
            self::OPERATOR_NE => $comparison !== 0,
            self::OPERATOR_LT => $comparison < 0,
            self::OPERATOR_LTE => $comparison <= 0,
            self::OPERATOR_GT => $comparison > 0,
            self::OPERATOR_GTE => $comparison >= 0,
            default => throw new RuntimeException("Invalid operator: $this->operator"),
        };
    }
}
