<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\fields\Country;

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
        return Craft::$app->getAddresses()->getCountryList(Craft::$app->language);
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
        if (!$this->field() instanceof Country) {
            // No longer a Country field
            return false;
        }

        return $this->matchValue($value);
    }
}
