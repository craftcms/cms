<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use CraftCms\Cms\Support\Security;

beforeEach(function () {
    $this->security = app(Security::class);
});

test('isSensitive', function (string $key, bool $expected) {
    expect($this->security->isSensitive($key))->toBe($expected);
})->with([
    ['password', true],
    ['password_reset', true],
    ['api_key', true],
    ['secret', true],
    ['sk', true],
    ['token', true],
    ['apiToken', true],
    ['userPassword', true],
    ['firstName', false],
    ['email', false],
    ['handle', false],
]);

test('redactIfSensitive', function (string $key, mixed $value, mixed $expected) {
    expect($this->security->redactIfSensitive($key, $value))->toBe($expected);
})->with([
    ['password', 'secret123', '•••••••••'],
    ['apiToken', 'abc-123', '•••••••'],
    ['firstName', 'John', 'John'],
    ['user', ['password' => 'secret123', 'name' => 'John'], ['password' => '•••••••••', 'name' => 'John']],
    ['nested', ['data' => ['token' => 'secret']], ['data' => ['token' => '••••••']]],
]);

test('isSystemDir', function () {
    $configPath = config_path('craft');
    $vendorPath = base_path('vendor');

    expect($this->security->isSystemDir($configPath))->toBeTrue();
    expect($this->security->isSystemDir($vendorPath))->toBeTrue();
    expect($this->security->isSystemDir('/tmp/random-path'))->toBeFalse();
});

test('custom sensitive keywords', function () {
    $security = new Security(['banana', 'apple']);

    expect($security->isSensitive('banana'))->toBeTrue();
    expect($security->isSensitive('apple'))->toBeTrue();
    expect($security->isSensitive('password'))->toBeFalse();

    expect($security->redactIfSensitive('banana', 'fruit'))->toBe('•••••');
});
