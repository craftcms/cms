<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Addresses;

test('addresses field reports nested address validation errors', function () {
    $value = [
        'new1' => [
            'title' => 'Home',
            'countryCode' => 'US',
        ],
    ];

    $result = EntryModel::factory()
        ->withField('addressesField', Addresses::class, [], value: $value)
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has('addressesField'))->toBeTrue();
});
