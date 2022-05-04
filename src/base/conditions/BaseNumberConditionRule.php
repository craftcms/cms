<?php

namespace craft\base\conditions;

/**
 * BaseNumberConditionRule provides a base implementation for condition rules that are composed of a number input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseNumberConditionRule extends BaseTextConditionRule
{
    /**
     * @inheritdoc
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_NE,
            self::OPERATOR_LT,
            self::OPERATOR_LTE,
            self::OPERATOR_GT,
            self::OPERATOR_GTE,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function inputType(): string
    {
        return 'number';
    }
}
