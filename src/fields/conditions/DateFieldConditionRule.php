<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseDateRangeConditionRule;
use craft\fields\Date;
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
        if (!$this->field() instanceof Date) {
            // No longer a Date field
            return false;
        }

        /** @var DateTime|null $value */
        return $this->matchValue($value);
    }
}
