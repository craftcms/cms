<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\fields\Country;
use yii\base\InvalidConfigException;

/**
 * Options field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 */
class CountryFieldConditionRule extends BaseMultiSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return Craft::$app->getAddresses()->getCountryList(Craft::$app->language);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        if (!$this->field() instanceof Country) {
            throw new InvalidConfigException();
        }

        return parent::inputHtml();
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?array
    {
        if (!$this->field() instanceof Country) {
            return null;
        }

        return $this->paramValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof Country) {
            return true;
        }

        return $this->matchValue($value);
    }
}
