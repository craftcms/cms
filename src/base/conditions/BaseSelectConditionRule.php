<?php

namespace craft\base\conditions;

use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * BaseSelectConditionRule provides a base implementation for condition rules that are composed of a select input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
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
     * @return array
     */
    abstract protected function options(): array;

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        $selectId = 'select';

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $selectId) .
            Cp::selectHtml([
                'id' => $selectId,
                'name' => 'value',
                'options' => $this->options(),
                'value' => $this->value,
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['value'], 'in', 'range' => fn() => $this->_validValues()],
        ]);
    }

    /**
     * Returns the valid option values.
     *
     * @return array
     */
    private function _validValues(): array
    {
        $options = [];
        foreach ($this->options() as $key => $value) {
            $options[] = is_array($value) && array_key_exists('value', $value) ? $value['value'] : $key;
        }
        return $options;
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param string $value
     * @return bool
     */
    protected function matchValue(string $value): bool
    {
        return $value === $this->value;
    }
}
