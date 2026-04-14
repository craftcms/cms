<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Typecast;

test('scalar properties', function (string $class, string $property, mixed $expected, mixed $value) {
    $config = [
        $property => $value,
    ];

    Typecast::properties($class, $config);

    expect($config)->toBe([
        $property => $expected,
    ]);
})->with([
    [GeneralConfig::class, 'aliases', ['foo', 'bar'], 'foo,bar'],
    [GeneralConfig::class, 'allowAdminChanges', true, 'yes'],
    [GeneralConfig::class, 'allowAdminChanges', false, 'no'],
    [GeneralConfig::class, 'allowAdminChanges', true, 'on'],
    [GeneralConfig::class, 'allowAdminChanges', false, 'off'],
    [GeneralConfig::class, 'allowAdminChanges', true, '1'],
    [GeneralConfig::class, 'allowAdminChanges', false, '0'],
    [GeneralConfig::class, 'allowAdminChanges', true, 'true'],
    [GeneralConfig::class, 'allowAdminChanges', false, 'false'],
    [GeneralConfig::class, 'allowAdminChanges', false, ''],
    [GeneralConfig::class, 'allowAdminChanges', false, 'whatever'],
    [GeneralConfig::class, 'baseCpUrl', null, ''],
    [GeneralConfig::class, 'blowfishHashCost', 123, 123],
    [GeneralConfig::class, 'isSystemLive', true, 'yes'],
    [GeneralConfig::class, 'isSystemLive', false, 'no'],
    [GeneralConfig::class, 'isSystemLive', true, 'on'],
    [GeneralConfig::class, 'isSystemLive', false, 'off'],
    [GeneralConfig::class, 'isSystemLive', true, '1'],
    [GeneralConfig::class, 'isSystemLive', false, '0'],
    [GeneralConfig::class, 'isSystemLive', true, 'true'],
    [GeneralConfig::class, 'isSystemLive', false, 'false'],
    [GeneralConfig::class, 'isSystemLive', null, ''],
    [GeneralConfig::class, 'isSystemLive', null, 'whatever'],
    [GeneralConfig::class, 'maxUploadFileSize', 123, '123'],
    [GeneralConfig::class, 'maxUploadFileSize', '123abc', '123abc'],
]);

test('datetime properties', function () {
    $now = now();

    $config = [
        'postDate' => $now->format(DateTime::ATOM),
        'expiryDate' => '',
    ];

    Typecast::properties(Entry::class, $config);

    expect($config['postDate'])->toBeInstanceOf(DateTime::class);
    expect($config['postDate']->getTimestamp())->toBe($now->getTimestamp());
    expect($config['expiryDate'])->toBeNull();
});

test('can identify datetime properties', function () {
    expect(Typecast::isDateTimeProperty(Entry::class, 'postDate'))->toBeTrue();
    expect(Typecast::isDateTimeProperty(Entry::class, 'expiryDate'))->toBeTrue();
    expect(Typecast::isDateTimeProperty(Entry::class, 'title'))->toBeFalse();
    expect(Typecast::isDateTimeProperty(Entry::class, 'doesNotExist'))->toBeFalse();
});

test('enum properties', function () {
    enum Suit: string
    {
        case Hearts = 'H';
        case Diamonds = 'D';
        case Clubs = 'C';
        case Spades = 'S';
    }

    class EnumModel
    {
        public Suit $suit;

        public Suit $anotherSuit;

        public ?Suit $nullableSuit = null;
    }

    $config = [
        'suit' => 'H',
        'anotherSuit' => Suit::Hearts,
        'nullableSuit' => '',
    ];

    Typecast::properties(EnumModel::class, $config);

    expect($config)->toBe([
        'suit' => Suit::Hearts,
        'anotherSuit' => Suit::Hearts,
        'nullableSuit' => null,
    ]);
});

test('isIntOrFloat', function (bool $expected, mixed $value) {
    expect(Typecast::isIntOrFloat($value))->toBe($expected);
})->with([
    [true, 0],
    [true, 0.5],
    [true, 10],
    [true, 10.5],
    [true, '0'],
    [true, '0.5'],
    [true, '0.50'],
    [true, '10'],
    [true, '10.5'],
    [false, '00'],
    [false, ' 0'],
    [false, '00.5'],
    [false, ' 0.5'],
    [false, ' '],
    [false, 'y'],
    [false, true],
    [false, []],
]);
