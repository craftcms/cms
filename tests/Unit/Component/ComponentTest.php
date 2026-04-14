<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use Illuminate\Support\Traits\Macroable;

class MacroableTestComponent extends Component
{
    use Macroable;

    public mixed $stored = null;
}

class ArrayableTestComponent extends Component
{
    public ?DateTime $dateCreated = null;

    public ?DateTimeImmutable $dateUpdated = null;

    public string $name = 'test';
}

beforeEach(function () {
    MacroableTestComponent::flushMacros();
});

test('can get property from macro getter', function () {
    MacroableTestComponent::macro('getFoo', fn () => 'macro-value');

    $component = new MacroableTestComponent;

    expect($component->foo)->toBe('macro-value');
});

test('can set property with macro setter', function () {
    MacroableTestComponent::macro('setFoo', function (mixed $value) {
        $this->stored = $value;
    });

    $component = new MacroableTestComponent;
    $component->foo = 'macro-value';

    expect($component->stored)->toBe('macro-value');
});

test('isset checks macro getter', function (mixed $macroResult, bool $expected) {
    MacroableTestComponent::macro('getFoo', fn () => $macroResult);

    $component = new MacroableTestComponent;

    expect(isset($component->foo))->toBe($expected);
})->with([
    ['macro-value', true],
    [null, false],
]);

test('throws write-only exception when only macro setter exists', function () {
    MacroableTestComponent::macro('setFoo', function () {});

    $component = new MacroableTestComponent;

    expect(fn () => $component->foo)
        ->toThrow(InvalidCallException::class, 'Getting write-only property');
});

test('throws read-only exception when only macro getter exists', function () {
    MacroableTestComponent::macro('getFoo', fn () => 'macro-value');

    $component = new MacroableTestComponent;

    expect(fn () => $component->foo = 'new-value')
        ->toThrow(InvalidCallException::class, 'Setting read-only property');
});

test('throws unknown property exception when no matching macro exists', function () {
    $component = new MacroableTestComponent;

    expect(fn () => $component->foo)
        ->toThrow(UnknownPropertyException::class, 'Getting unknown property');
});

test('serializes datetime properties to iso8601', function () {
    $component = new ArrayableTestComponent([
        'dateCreated' => new DateTime('2024-01-02 03:04:05', new DateTimeZone('America/Los_Angeles')),
        'dateUpdated' => new DateTimeImmutable('2024-02-03 04:05:06', new DateTimeZone('America/New_York')),
    ]);

    expect($component->toArray())->toMatchArray([
        'dateCreated' => '2024-01-02T11:04:05+00:00',
        'dateUpdated' => '2024-02-03T09:05:06+00:00',
        'name' => 'test',
    ]);
});
