<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Conditions\AddressCondition;
use CraftCms\Cms\Address\Conditions\AddressLine1ConditionRule;
use CraftCms\Cms\Address\Conditions\AddressLine2ConditionRule;
use CraftCms\Cms\Address\Conditions\AddressLine3ConditionRule;
use CraftCms\Cms\Address\Conditions\AdministrativeAreaConditionRule;
use CraftCms\Cms\Address\Conditions\CountryConditionRule;
use CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule;
use CraftCms\Cms\Address\Conditions\FieldConditionRule;
use CraftCms\Cms\Address\Conditions\FullNameConditionRule;
use CraftCms\Cms\Address\Conditions\LocalityConditionRule;
use CraftCms\Cms\Address\Conditions\OrganizationConditionRule;
use CraftCms\Cms\Address\Conditions\OrganizationTaxIdConditionRule;
use CraftCms\Cms\Address\Conditions\PostalCodeConditionRule;
use CraftCms\Cms\Address\Conditions\SortingCodeConditionRule;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Models\Address as AddressModel;

it('matchElement with text-based rules', function (string $ruleClass, string $property, string $factoryValue, string $ruleValue, bool $expected) {
    $element = AddressModel::factory()->createElement([$property => $factoryValue]);

    $condition = new AddressCondition(Address::class);
    $rule = $condition->createConditionRule($ruleClass);
    $rule->operator = '=';
    $rule->value = $ruleValue;

    expect($rule->matchElement($element))->toBe($expected);
})->with([
    'address line 1 matches' => [AddressLine1ConditionRule::class, 'addressLine1', 'Alpha', 'Alpha', true],
    'address line 1 does not match' => [AddressLine1ConditionRule::class, 'addressLine1', 'Beta', 'Alpha', false],
    'address line 2 matches' => [AddressLine2ConditionRule::class, 'addressLine2', 'Alpha', 'Alpha', true],
    'address line 2 does not match' => [AddressLine2ConditionRule::class, 'addressLine2', 'Beta', 'Alpha', false],
    'address line 3 matches' => [AddressLine3ConditionRule::class, 'addressLine3', 'Alpha', 'Alpha', true],
    'address line 3 does not match' => [AddressLine3ConditionRule::class, 'addressLine3', 'Beta', 'Alpha', false],
    'sorting code matches' => [SortingCodeConditionRule::class, 'sortingCode', 'Alpha', 'Alpha', true],
    'sorting code does not match' => [SortingCodeConditionRule::class, 'sortingCode', 'Beta', 'Alpha', false],
    'postal code matches' => [PostalCodeConditionRule::class, 'postalCode', 'Alpha', 'Alpha', true],
    'postal code does not match' => [PostalCodeConditionRule::class, 'postalCode', 'Beta', 'Alpha', false],
    'dependent locality matches' => [DependentLocalityConditionRule::class, 'dependentLocality', 'Alpha', 'Alpha', true],
    'dependent locality does not match' => [DependentLocalityConditionRule::class, 'dependentLocality', 'Beta', 'Alpha', false],
    'organization tax id matches' => [OrganizationTaxIdConditionRule::class, 'organizationTaxId', 'Alpha', 'Alpha', true],
    'organization tax id does not match' => [OrganizationTaxIdConditionRule::class, 'organizationTaxId', 'Beta', 'Alpha', false],
    'organization matches' => [OrganizationConditionRule::class, 'organization', 'Alpha', 'Alpha', true],
    'organization does not match' => [OrganizationConditionRule::class, 'organization', 'Beta', 'Alpha', false],
    'locality matches' => [LocalityConditionRule::class, 'locality', 'Alpha', 'Alpha', true],
    'locality does not match' => [LocalityConditionRule::class, 'locality', 'Beta', 'Alpha', false],
    'full name matches' => [FullNameConditionRule::class, 'fullName', 'Alpha', 'Alpha', true],
    'full name does not match' => [FullNameConditionRule::class, 'fullName', 'Beta', 'Alpha', false],
]);

it('modifyQuery filters addresses by text-based rules', function (string $ruleClass, string $property, string $operator, string $ruleValue, string $matchFactoryValue, string $otherFactoryValue, string $expectedFieldValue) {
    AddressModel::factory()->create([$property => $matchFactoryValue]);
    AddressModel::factory()->create([$property => $otherFactoryValue]);

    $condition = new AddressCondition(Address::class);
    $rule = $condition->createConditionRule($ruleClass);
    $rule->operator = $operator;
    $rule->value = $ruleValue;

    $query = Address::find();
    $rule->modifyQuery($query, $query);

    $results = $query->all();

    expect($results)->toHaveCount(1);
    expect($results[0]->$property)->toBe($expectedFieldValue);
})->with([
    'address line 1 exact match' => [AddressLine1ConditionRule::class, 'addressLine1', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'address line 2 exact match' => [AddressLine2ConditionRule::class, 'addressLine2', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'address line 3 exact match' => [AddressLine3ConditionRule::class, 'addressLine3', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'sorting code exact match' => [SortingCodeConditionRule::class, 'sortingCode', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'postal code exact match' => [PostalCodeConditionRule::class, 'postalCode', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'dependent locality exact match' => [DependentLocalityConditionRule::class, 'dependentLocality', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'organization tax id exact match' => [OrganizationTaxIdConditionRule::class, 'organizationTaxId', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'organization exact match' => [OrganizationConditionRule::class, 'organization', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'locality exact match' => [LocalityConditionRule::class, 'locality', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'full name exact match' => [FullNameConditionRule::class, 'fullName', '=', 'Findme', 'Findme', 'Notme', 'Findme'],
    'locality begins with' => [LocalityConditionRule::class, 'locality', 'bw', 'Port', 'Portland', 'Seattle', 'Portland'],
]);

describe('CountryConditionRule', function () {
    it('matchElement returns true when country code is selected', function () {
        $element = AddressModel::factory()->createElement(['countryCode' => 'US']);

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(CountryConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['US'];

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('matchElement returns false when country code is not selected', function () {
        $element = AddressModel::factory()->createElement(['countryCode' => 'CA']);

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(CountryConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['US'];

        expect($rule->matchElement($element))->toBeFalse();
    });

    it('modifyQuery filters addresses by country code', function () {
        AddressModel::factory()->create(['countryCode' => 'US']);
        AddressModel::factory()->create(['countryCode' => 'CA']);

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(CountryConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['US'];

        $query = Address::find();
        $rule->modifyQuery($query, $query);

        $results = $query->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->countryCode)->toBe('US');
    });
});

describe('AdministrativeAreaConditionRule', function () {
    it('matchElement returns true when administrative area is selected', function () {
        $element = AddressModel::factory()->createElement(['administrativeArea' => 'CA']);

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(AdministrativeAreaConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['CA'];

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('matchElement returns false when administrative area is not selected', function () {
        $element = AddressModel::factory()->createElement(['administrativeArea' => 'NY']);

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(AdministrativeAreaConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['CA'];

        expect($rule->matchElement($element))->toBeFalse();
    });

    it('modifyQuery filters addresses by administrative area regardless of the countryCode property', function () {
        AddressModel::factory()->create(['administrativeArea' => 'CA']);
        AddressModel::factory()->create(['administrativeArea' => 'NY']);

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(AdministrativeAreaConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['CA'];
        $rule->countryCode = 'FR';

        $query = Address::find();
        $rule->modifyQuery($query, $query);

        $results = $query->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->administrativeArea)->toBe('CA');
    });
});

describe('FieldConditionRule', function () {
    it('matchElement returns true for a top-level address when operator is empty', function () {
        $element = AddressModel::factory()->createElement();

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(FieldConditionRule::class);
        $rule->operator = 'empty';

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('matchElement returns false for a top-level address when operator is notempty', function () {
        $element = AddressModel::factory()->createElement();

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(FieldConditionRule::class);
        $rule->operator = 'notempty';

        expect($rule->matchElement($element))->toBeFalse();
    });

    it('modifyQuery with empty operator includes top-level addresses with no field', function () {
        $addressModel = AddressModel::factory()->create();

        $condition = new AddressCondition(Address::class);
        $rule = $condition->createConditionRule(FieldConditionRule::class);
        $rule->operator = 'empty';

        $query = Address::find();
        $rule->modifyQuery($query, $query);

        $resultIds = collect($query->all())->pluck('id')->toArray();

        expect($resultIds)->toContain($addressModel->id);
    });
});
