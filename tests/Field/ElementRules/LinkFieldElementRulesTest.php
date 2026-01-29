<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Tests\Support\FieldElementRulesHelper;

test('link field validates max length', function () {
    [$invalidEntry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'shortLink',
        fieldType: Link::class,
        fieldSettings: ['types' => ['url'], 'maxLength' => 5],
        value: new LinkData('https://example.com', new UrlType),
    );
    $invalidEntry->validate();

    expect($invalidEntry->errors()->has('shortLink'))->toBeTrue();

    [$validEntry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'normalLink',
        fieldType: Link::class,
        fieldSettings: ['types' => ['url'], 'maxLength' => 255],
        value: new LinkData('https://example.com', new UrlType),
    );
    $validEntry->validate();

    expect($validEntry->errors()->has('normalLink'))->toBeFalse();
});
