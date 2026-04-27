<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;

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
