<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;

test('link field validates shared link settings', function () {
    $field = new Link([
        'name' => 'Link',
        'handle' => 'link',
        'types' => ['url'],
        'advancedFields' => ['target', 'download'],
    ]);
    $invalidTypeField = new Link([
        'name' => 'Link',
        'handle' => 'link',
        'types' => ['missing'],
    ]);
    $invalidAdvancedField = new Link([
        'name' => 'Link',
        'handle' => 'link',
        'advancedFields' => ['missing'],
    ]);

    expect($field->validate(['types', 'typeSettings', 'showLabelField', 'advancedFields']))->toBeTrue()
        ->and($invalidTypeField->validate(['types']))->toBeFalse()
        ->and($invalidTypeField->errors()->has('types'))->toBeTrue()
        ->and($invalidAdvancedField->validate(['advancedFields']))->toBeFalse()
        ->and($invalidAdvancedField->errors()->has('advancedFields'))->toBeTrue();
});

test('link field validates max length', function () {
    $invalidResult = EntryModel::factory()
        ->withField('shortLink', Link::class, ['types' => ['url'], 'maxLength' => 5], value: new LinkData('https://example.com', new UrlType))
        ->createElementWithFields(save: false);
    $invalidResult->element->validate();

    expect($invalidResult->element->errors()->has('shortLink'))->toBeTrue();

    $validResult = EntryModel::factory()
        ->withField('normalLink', Link::class, ['types' => ['url'], 'maxLength' => 255], value: new LinkData('https://example.com', new UrlType))
        ->createElementWithFields(save: false);
    $validResult->element->validate();

    expect($validResult->element->errors()->has('normalLink'))->toBeFalse();
});
