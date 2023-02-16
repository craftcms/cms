<?php

namespace craft\elements\conditions\addresses;

use craft\elements\Address;
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
    public ?string $elementType = Address::class;

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            CountryConditionRule::class,
            AdministrativeAreaConditionRule::class,
        ]);
    }
}
