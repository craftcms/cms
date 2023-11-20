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
            CountryConditionRule::class,
            AdministrativeAreaConditionRule::class,
            LocalityConditionRule::class,
            DependentLocalityConditionRule::class,
            PostalCodeConditionRule::class,
            OrganizationConditionRule::class,
            OrganizationTaxIdConditionRule::class,
            AddressLine1ConditionRule::class,
            AddressLine2ConditionRule::class,

        ]);
    }
}
