<?php

namespace craft\elements\conditions\addresses;

use craft\elements\conditions\ElementCondition;

/**
 * Asset query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AddressCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
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
