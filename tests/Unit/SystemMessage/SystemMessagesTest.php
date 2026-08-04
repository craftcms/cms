<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use Illuminate\Contracts\Foundation\Application;

beforeEach(function () {
    I18N::shouldReceive('getSiteLocaleIds')->andReturn(collect([app()->getLocale()]));
    I18N::shouldReceive('translate')->andReturnUsing(fn (string $message) => $message);
});

it('resolves registered factories in message-key order and omits removed messages', function () {
    $registry = app(SystemMessages::class);

    $registry->register('custom', fn () => systemMessage('custom', 'First'));
    $registry->register('after_custom', function (Application $application) {
        expect($application)->toBe(app());

        return systemMessage('after_custom', 'After');
    });
    $registry->register('custom', fn () => systemMessage('custom', 'Updated'));
    $registry->remove('test_email', 'missing');

    $messages = $registry->messages();
    $keys = $messages->keys()->all();
    $sortedKeys = $keys;
    sort($sortedKeys);

    expect($keys)->toBe($sortedKeys)
        ->toContain('custom', 'after_custom')
        ->not()->toContain('test_email')
        ->and($messages['custom']->heading)->toBe('Updated');
});

it('does not invoke factories during registration', function () {
    $resolved = false;
    $registry = app(SystemMessages::class);

    $registry->register('lazy', fn () => systemMessage('lazy'));
    $registry->register('lazy', function () use (&$resolved) {
        $resolved = true;

        return systemMessage('lazy');
    });

    expect($resolved)->toBeFalse();
});

it('rejects empty keys immediately', function () {
    $registry = app(SystemMessages::class);

    expect(fn () => $registry->register('', fn () => systemMessage('message')))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $registry->remove(''))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects invalid factory results when resolving', function (string $key, Closure $resolve) {
    $registry = app(SystemMessages::class);
    $registry->register($key, $resolve);

    expect(fn () => $registry->messages())->toThrow(InvalidArgumentException::class);
})->with([
    'invalid result' => ['invalid', fn () => new stdClass],
    'mismatched key' => ['expected', fn () => systemMessage('actual')],
]);

function systemMessage(string $key, string $heading = 'Heading'): SystemMessage
{
    return new SystemMessage([
        'key' => $key,
        'heading' => $heading,
        'subject' => 'Subject',
        'body' => 'Body',
    ]);
}
