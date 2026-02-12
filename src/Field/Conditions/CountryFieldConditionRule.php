<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Country;
use yii\base\InvalidConfigException;

class CountryFieldConditionRule extends BaseMultiSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * {@inheritdoc}
     */
    protected function options(): array
    {
        return app(Addresses::class)->getCountryList(app()->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(): string
    {
        if (! $this->field() instanceof Country) {
            throw new InvalidConfigException;
        }

        return parent::inputHtml();
    }

    /**
     * {@inheritdoc}
     */
    protected function elementQueryParam(): ?array
    {
        if (! $this->field() instanceof Country) {
            return null;
        }

        return $this->paramValue();
    }

    /**
     * {@inheritdoc}
     */
    protected function matchFieldValue($value): bool
    {
        if (! $this->field() instanceof Country) {
            return true;
        }

        return $this->matchValue($value);
    }
}
