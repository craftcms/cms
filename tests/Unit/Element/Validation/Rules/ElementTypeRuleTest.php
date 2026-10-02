<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Entry\Elements\Entry;

it('validates element type class strings', function (mixed $input, bool $expected) {
    $rule = new ElementTypeRule;
    $valid = true;

    $rule->validate('elementType', $input, function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBe($expected);
})->with([
    'element type' => [Entry::class, true],
    'missing class' => ['App\\MissingElement', false],
    'non-element class' => [stdClass::class, false],
    'integer' => [123, false],
    'array' => [['type' => Entry::class], false],
]);

it('exposes the same validity check used by non-validator callers', function () {
    expect(ElementTypeRule::isValid(Entry::class))->toBeTrue()
        ->and(ElementTypeRule::isValid(stdClass::class))->toBeFalse();
});
