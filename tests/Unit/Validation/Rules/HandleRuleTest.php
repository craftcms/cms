<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Rules\HandleRule;

beforeEach(function () {
    $this->reservedWords = ['bird', 'is', 'the', 'word'];
    $this->rule = new HandleRule($this->reservedWords);
});

it('can set reserved words', function () {
    foreach ($this->reservedWords as $reservedWord) {
        $valid = true;

        $this->rule->validate('handle', $reservedWord, function () use (&$valid) {
            $valid = false;
        });

        expect($valid)->toBeFalse();
    }
});

it('validates handles', function (string $input, bool $expected) {
    $valid = true;

    $this->rule->validate('handle', $input, function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBe($expected);
})->with([
    ['iamAHandle', true],
    ['iam1Handle', true],
    ['ASDFGHJKLQWERTYUIOPZXCVBNM', true],
    ['iam!Handle', false],
    ['!@#$%^&*()', false],
    ['🔥', false],
    ['123', false],
    ['iam A Handle', false],
]);
