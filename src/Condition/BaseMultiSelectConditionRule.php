<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use Override;
use RuntimeException;

/**
 * BaseMultiSelectConditionRule provides a base implementation for condition rules that are composed of a multi-select input.
 */
abstract class BaseMultiSelectConditionRule extends BaseConditionRule
{
    #[Override]
    public string $operator = self::OPERATOR_IN;

    /**
     * @var string[]
     */
    private array $_values = [];

    /** @var string[] */
    public array $values {
        get => $this->getValues();
        set {
            $this->setValues($value);
        }
    }

    /**
     * @var bool Whether “has a value” and “is empty” operators should be available to the condition rule.
     */
    protected bool $includeEmptyOperators = false;

    public function __construct(object|array $config = [])
    {
        parent::__construct($config);

        if ($this->includeEmptyOperators) {
            $this->reloadOnOperatorChange = true;
        }
    }

    /**
     * Returns the operators that should be allowed for this rule.
     *
     * @return string[]
     */
    #[Override]
    protected function operators(): array
    {
        $operators = [
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
        ];

        if ($this->includeEmptyOperators) {
            array_push($operators, self::OPERATOR_NOT_EMPTY, self::OPERATOR_EMPTY);
        }

        return $operators;
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->_values;
    }

    /**
     * @param  string|string[]  $values
     */
    public function setValues(array|string $values): void
    {
        if ($values === '') {
            $this->_values = [];
        } else {
            $this->_values = Arr::wrap($values);
        }
    }

    /** @return array<string, mixed> */
    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'values' => $this->_values,
        ]);
    }

    /**
     * Defines the selectable options.
     *
     * Options can be expressed as value/label pairs, or as arrays with `value` and `label` keys.
     *
     * @return string[]|array{value:string,label:string}[]
     */
    abstract protected function options(): array;

    #[Override]
    protected function inputHtml(): string
    {
        if (! in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            return '';
        }

        $multiSelectId = 'multiselect';

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $multiSelectId).
            FormFields::selectizeHtml([
                'id' => $multiSelectId,
                'class' => 'flex-grow',
                'name' => 'values',
                'values' => $this->_values,
                'options' => $this->options(),
                'multi' => true,
            ]);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'values' => ['array'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for {@see QueryParam::parse} based on the selected operator.
     *
     * @param  callable|null  $normalizeValue  Method for normalizing a given selected value.
     * @return string|string[]|null
     */
    protected function paramValue(?callable $normalizeValue = null): string|array|null
    {
        if (in_array($this->operator, [self::OPERATOR_EMPTY, self::OPERATOR_NOT_EMPTY])) {
            return match ($this->operator) {
                self::OPERATOR_NOT_EMPTY => ':notempty:',
                self::OPERATOR_EMPTY => ':empty:',
            };
        }

        $values = [];
        foreach ($this->_values as $value) {
            if ($normalizeValue !== null) {
                $value = $normalizeValue($value);
                if ($value === null) {
                    continue;
                }
            }
            $values[] = Query::escapeParam((string) $value);
        }

        if (! $values) {
            return null;
        }

        return match ($this->operator) {
            self::OPERATOR_IN => $values,
            self::OPERATOR_NOT_IN => array_merge(['not'], $values),
            default => throw new RuntimeException("Invalid operator: $this->operator"),
        };
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param  string|string[]|null  $value
     */
    protected function matchValue(array|string|null $value): bool
    {
        if (in_array($this->operator, [self::OPERATOR_EMPTY, self::OPERATOR_NOT_EMPTY])) {
            return match ($this->operator) {
                self::OPERATOR_EMPTY => empty($value),
                self::OPERATOR_NOT_EMPTY => ! empty($value),
            };
        }

        if (! $this->_values) {
            return true;
        }

        if ($value === '' || $value === null) {
            $value = [];
        } else {
            $value = (array) $value;
        }

        return match ($this->operator) {
            self::OPERATOR_IN => ! empty(array_intersect($value, $this->_values)),
            self::OPERATOR_NOT_IN => empty(array_intersect($value, $this->_values)),
            default => throw new RuntimeException("Invalid operator: $this->operator"),
        };
    }
}
