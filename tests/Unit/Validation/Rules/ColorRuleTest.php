<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Rules\ColorRule;

it('can normalize a color', function (string $expected, string $color) {
    expect(ColorRule::normalizeColor($color))->toBe($expected);
})->with([
    ['#ffc10e', 'ffc10e'],
    ['#', '#'],
    ['#1234567890qwertyuiop!@#$%^&*()', '1234567890qwertyuiop!@#$%^&*()'],
    ['#12', '12'],
    ['#!!@@##', '!@#'],
    'three-chars-becomes-six' => ['#aassdd', 'asd'],
    ['#aassdd', 'ASD'],
    'four-chars-becomes-eight' => ['#aa2222dd', 'a22d'],
    ['#aabbcc80', '#AABBCC80'],
    'opaque-alpha-is-dropped' => ['#aabbcc', '#aabbccff'],
    'opaque-shorthand-alpha-is-dropped' => ['#aabbcc', '#abcf'],
]);

it('validates', function (string $input, bool $expected) {
    $rule = new ColorRule;

    $valid = true;

    $rule->validate('color', $input, function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBe($expected);
})->with([
    ['#ffc', true],
    ['#ffc10e', true],
    ['ffc10e', true],
    ['#ffc10eaaaaaaaaa', false],
    ['fffc10e', false],
    ['xxx', false],
    ['#ffc1', false],
    ['#ffc10e80', false],
    ['#ffc10eff', true],
    ['#ffc1e', false],
    ['#ff', false],
    ['#f', false],
    ['#', false],
    ['rgba(255, 0, 0, 0.2)', false],
    ['255, 0, 0, 0.2', false],
]);

it('validates alpha channels when allowed', function (string $input, bool $expected) {
    $rule = new ColorRule(allowAlpha: true);

    $valid = true;

    $rule->validate('color', $input, function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBe($expected);
})->with([
    ['#ffc10e', true],
    ['#ffc10e80', true],
    ['ffc10e80', true],
    ['#ffc1', true],
    ['transparent', true],
    ['#ffc10e8', false],
    ['#ffc10e800', false],
    ['#ffc10egg', false],
    ['rgba(255, 0, 0, 0.2)', false],
]);
