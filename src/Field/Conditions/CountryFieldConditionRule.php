<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Country;
use RuntimeException;

class CountryFieldConditionRule extends BaseMultiSelectConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    protected function options(): array
    {
        return app(Addresses::class)->getCountryList(app()->getLocale());
    }

    #[\Override]
    protected function inputHtml(): string
    {
        if (! $this->field() instanceof Country) {
            throw new RuntimeException;
        }

        return parent::inputHtml();
    }

    /** @return list<string>|null */
    protected function elementQueryParam(): ?array
    {
        if (! $this->field() instanceof Country) {
            return null;
        }

        return $this->paramValue();
    }

    /** @param string|null $value */
    protected function matchFieldValue(mixed $value): bool
    {
        if (! $this->field() instanceof Country) {
            return true;
        }

        return $this->matchValue($value);
    }
}
