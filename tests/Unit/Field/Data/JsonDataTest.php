<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Data\JsonData;

it('preserves JSON values including former error keys', function (mixed $value) {
    $data = new JsonData($value);

    expect(json_decode($data->getJson(), true, flags: JSON_THROW_ON_ERROR))->toBe($value);
    expect($data->serialize())->toBe($value);
    expect($data->getValue())->toBe($value);

    if (is_array($value)) {
        expect(iterator_to_array($data))->toBe($value);
        foreach ($value as $key => $item) {
            expect(isset($data[$key]))->toBeTrue();
            expect($data[$key])->toBe($item);
        }

        $data['added'] = 1;
        expect($data['added'])->toBe(1);
        unset($data['added']);
        expect($data->serialize())->toBe($value);
    }
})->with([
    'error key' => [['__ERROR__' => 'User value']],
    'value key' => [['__VALUE__' => 'User value']],
    'both keys' => [['__ERROR__' => true, '__VALUE__' => 123]],
    'null key value' => [['__ERROR__' => null]],
    'string' => ['text'],
    'integer' => [42],
    'boolean' => [false],
    'null' => [null],
]);

it('returns null for missing method calls without parameters', function () {
    $data = new JsonData(['foo' => 'bar']);

    expect($data->missingMethod())->toBeNull();
});

it('throws for missing method calls with parameters', function () {
    $data = new JsonData(['foo' => 'bar']);

    $data->missingMethod('value');
})->throws(BadMethodCallException::class);
