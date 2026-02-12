<?php

namespace craft\fields\conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Field\Date;
use DateTime;
use yii\base\InvalidConfigException;

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
    protected function inputHtml(): string
    {
        if (!$this->field() instanceof Date) {
            throw new InvalidConfigException();
        }

        return parent::inputHtml();
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): array|string|null
    {
        if (!$this->field() instanceof Date) {
            return null;
        }

        return $this->queryParamValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof Date) {
            return true;
        }

        /** @var DateTime|null $value */
        return $this->matchValue($value);
    }
}
