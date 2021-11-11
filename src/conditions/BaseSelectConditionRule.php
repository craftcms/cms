<?php

namespace craft\conditions;

use craft\helpers\Cp;

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
    public function getHtml(array $options = []): string
    {
        return Cp::selectHtml([
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
}
