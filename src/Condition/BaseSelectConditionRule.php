<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Nodes\Field;
use Illuminate\Validation\Rule;
use Override;

/**
 * BaseSelectConditionRule provides a base implementation for condition rules that are composed of a select input.
 */
abstract class BaseSelectConditionRule extends BaseConditionRule
{
    /**
     * @var string The selected option’s value.
     */
    public string $value = '';

    /**
     * Returns the selectable options in the select input.
     *
     * @return array<int|string, string|array{value: string, label: string}>
     */
    abstract protected function options(): array;

    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    /** @return list<Node> */
    #[Override]
    protected function inputNodes(): array
    {
        return [Field::make($this->getLabel(), Choice::make('value')->options($this->formOptions($this->options()))->withoutPlaceholder()->value($this->value))];
    }

    #[Override]
    public function getRules(): array
    {
        $values = $this->_validValues();

        return array_merge(parent::getRules(), [
            'value' => [Rule::requiredIf(! in_array('', $values, true)), Rule::in($values)],
        ]);
    }

    /**
     * Returns the valid option values.
     *
     * @return string[]
     */
    private function _validValues(): array
    {
        $options = [];

        foreach ($this->options() as $key => $value) {
            $options[] = is_array($value) && array_key_exists('value', $value)
                ? $value['value']
                : $key;
        }

        return $options;
    }

    /**
     * Returns whether the condition rule matches the given value.
     */
    protected function matchValue(string $value): bool
    {
        return $value === $this->value;
    }
}
