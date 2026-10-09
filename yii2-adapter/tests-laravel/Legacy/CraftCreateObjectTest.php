<?php

declare(strict_types=1);

use yii\base\InvalidConfigException;

test('createObject rejects configs with both class keys', function(array $config): void {
    expect(fn() => Craft::createObject($config))
        ->toThrow(InvalidConfigException::class, '`__class` and `class` cannot both be specified.');
})->with([
    'both set' => [['class' => stdClass::class, '__class' => stdClass::class]],
    'null class' => [['class' => null, '__class' => stdClass::class]],
    'null __class' => [['class' => stdClass::class, '__class' => null]],
]);

test('createObject accepts a single class key', function(string $key): void {
    expect(Craft::createObject([$key => stdClass::class]))->toBeInstanceOf(stdClass::class);
})->with(['class', '__class']);
