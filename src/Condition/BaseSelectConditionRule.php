<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use craft\helpers\Cp;
use CraftCms\Cms\Support\Html;
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
     */
    abstract protected function options(): array;

    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    #[Override]
    protected function inputHtml(): string
    {
        $selectId = 'select';

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $selectId).
            Cp::selectHtml([
                'id' => $selectId,
                'name' => 'value',
                'options' => $this->options(),
                'value' => $this->value,
            ]);
    }

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'value' => ['required', Rule::in($this->_validValues())],
        ]);
    }

    /**
     * Returns the valid option values.
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
