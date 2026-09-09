<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Query;
use Override;

use function CraftCms\Cms\t;

/**
 * BaseNumberConditionRule provides a base implementation for condition rules that are composed of a number input.
 */
abstract class BaseNumberConditionRule extends BaseTextConditionRule
{
    protected const string OPERATOR_BETWEEN = 'between';

    public string $maxValue = '';

    /**
     * @var int|float|null The `step` value the input should have.
     */
    public int|float|null $step = 1;

    /** @return array<string, mixed> */
    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'maxValue' => $this->maxValue,
            'step' => $this->step,
        ]);
    }

    /** @return string[] */
    #[Override]
    protected function operators(): array
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_NE,
            self::OPERATOR_LT,
            self::OPERATOR_LTE,
            self::OPERATOR_GT,
            self::OPERATOR_GTE,
            self::OPERATOR_BETWEEN,
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
        ];
    }

    #[Override]
    protected function operatorLabel(string $operator): string
    {
        if ($operator === self::OPERATOR_BETWEEN) {
            return t('is between…');
        }

        return parent::operatorLabel($operator);
    }

    #[Override]
    protected function inputType(): string
    {
        return 'number';
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'maxValue' => ['nullable', 'numeric'],
            'step' => ['nullable', 'numeric'],
        ]);
    }

    /** @return list<Node> */
    #[Override]
    protected function inputNodes(): array
    {
        if ($this->operator !== self::OPERATOR_BETWEEN) {
            $nodes = parent::inputNodes();
            foreach ($nodes as $node) {
                $control = $node->getControl();
                if ($control instanceof Text) {
                    $control->step($this->step ?? 'any');
                }
            }

            return $nodes;
        }

        return [
            Field::make(t('Min Value'), Text::make('value')->inputType('number')->step($this->step ?? 'any')->value($this->value)),
            Field::make(t('Max Value'), Text::make('maxValue')->inputType('number')->step($this->step ?? 'any')->value($this->maxValue))
                ->tip(t('The values are matched inclusively.')),
        ];
    }

    /** @return string|string[]|null */
    #[Override]
    protected function paramValue(): string|array|null
    {
        if ($this->operator !== self::OPERATOR_BETWEEN) {
            return parent::paramValue();
        }

        if ($this->value === '' && $this->maxValue === '') {
            return null;
        }

        if ($this->maxValue === '') {
            return '>= '.Query::escapeParam($this->value);
        }

        if ($this->value === '') {
            return '<= '.Query::escapeParam($this->maxValue);
        }

        return sprintf('and, >= %s, <= %s', Query::escapeParam($this->value), Query::escapeParam($this->maxValue));
    }

    #[Override]
    protected function matchValue(mixed $value): bool
    {
        if ($this->operator !== self::OPERATOR_BETWEEN) {
            return parent::matchValue($value);
        }

        if ($this->value === '' && $this->maxValue === '') {
            return true;
        }

        if ($this->value !== '' && $value < $this->value) {
            return false;
        }

        if ($this->maxValue !== '' && $value > $this->maxValue) {
            return false;
        }

        return true;
    }
}
