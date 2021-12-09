<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\fields\BaseOptionsField;
use craft\helpers\Db;

/**
 * Options field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class OptionsFieldConditionRule extends BaseMultiSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    protected function options(): array
    {
        /** @var BaseOptionsField $field */
        $field = $this->_field;
        return collect($field->options)
            ->filter(fn(array $option) => !empty($option['value']) && !empty($option['label']))
            ->map(fn(array $option) => [
                'value' => $option['value'],
                'label' => $option['label'],
            ])
            ->all();
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): array
    {
        return collect($this->getValues())
            ->map(fn(string $value) => Db::escapeParam($value))
            ->all();
    }
}
