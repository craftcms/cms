<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Validation\Rules\LanguageRule;

it('validates', function (?string $errorMessage, string $value, bool $onlySiteLangs = true) {
    I18N::shouldReceive('getSiteLocaleIds')
        ->andReturn(collect(['nl', 'en-US']))
        ->shouldReceive('translate')
        ->andReturnUsing(fn ($message) => $message)
        ->shouldReceive('getAllLocaleIds')
        ->andReturn(collect(['nl', 'en-US', 'de']));

    $rule = new LanguageRule($onlySiteLangs);

    $error = null;

    $rule->validate('language', $value, function (string $message) use (&$error) {
        $error = $message;
    });

    expect($error)->toBe($errorMessage);
})->with([
    ['{value} is not a valid site language.', 'nolang'],
    [null, 'en-US'],
    [null, 'nl'],
    ['{value} is not a valid site language.', 'de'],
    [null, 'de', false],
    ['{value} is not a valid site language.', 'nolang', false],
]);

it('can pass a custom message', function () {
    $rule = new LanguageRule(message: 'This is a custom message');

    $rule->validate('language', 'not-a-language', function ($message) {
        expect($message)->toBe('This is a custom message');
    });
});
