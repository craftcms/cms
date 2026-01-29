<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Tests\Support\FieldElementRulesHelper;

test('addresses field reports nested address validation errors', function () {
    $value = [
        'new1' => [
            'title' => 'Home',
            'countryCode' => 'US',
        ],
    ];

    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'addressesField',
        fieldType: Addresses::class,
        fieldSettings: [],
        value: $value,
        scenario: Element::SCENARIO_LIVE,
    );

    $entry->validate();

    expect($entry->errors()->has('addressesField'))->toBeTrue();
});
