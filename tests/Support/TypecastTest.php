<?php

declare(strict_types=1);

use craft\elements\Entry;
use CraftCms\Cms\Config\GeneralConfig;
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
    [GeneralConfig::class, 'allowAdminChanges', true, '1'],
    [GeneralConfig::class, 'allowUpdates', false, '0'],
    [GeneralConfig::class, 'baseCpUrl', null, ''],
    [GeneralConfig::class, 'blowfishHashCost', 123, 123],
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
