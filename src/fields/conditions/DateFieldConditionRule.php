<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseDateRangeConditionRule;
use DateTime;

/**
 * Date field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DateFieldConditionRule extends BaseDateRangeConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): array|string|null
    {
        return $this->queryParamValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var DateTime|null $value */
        return $this->matchValue($value);
    }
}
