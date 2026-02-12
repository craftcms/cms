<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CraftCms\Cms\Element\Conditions\ElementCondition;

final class AddressCondition extends ElementCondition
{
    #[\Override]
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            AddressLine1ConditionRule::class,
            AddressLine2ConditionRule::class,
            AddressLine3ConditionRule::class,
            AdministrativeAreaConditionRule::class,
            CountryConditionRule::class,
            DependentLocalityConditionRule::class,
            FieldConditionRule::class,
            FullNameConditionRule::class,
            LocalityConditionRule::class,
            OrganizationConditionRule::class,
            OrganizationTaxIdConditionRule::class,
            PostalCodeConditionRule::class,
            SortingCodeConditionRule::class,
        ]);
    }
}
