<?php

use CraftCms\Cms\Shared\Rules\LanguageRule;

it('validates', function (bool $expected, string $value, bool $onlySiteLangs = true) {
    $rule = new LanguageRule($onlySiteLangs);

    $failed = false;

    $rule->validate('language', $value, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBe($expected);
})->with([
    [true, 'nolang'],
    [false, 'en-US'],
    [true, 'de'],
    [false, 'de', false],
    [true, 'nolang', false],
]);

it('can pass a custom message', function () {
    $rule = new LanguageRule(message: 'This is a custom message');

    $rule->validate('language', 'not-a-language', function ($message) {
        expect($message)->toBe('This is a custom message');
    });
});
