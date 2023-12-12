<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;

/**
 * Options field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 */
class CountryFieldConditionRule extends BaseMultiSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    protected function options(): array
    {
        return Craft::$app->getAddresses()->getCountryRepository()->getList(Craft::$app->language);
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?array
    {
        return $this->paramValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        return $this->matchValue($value);
    }
}
