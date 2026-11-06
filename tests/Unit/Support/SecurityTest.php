<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\Support;

use CraftCms\Cms\Support\Security;
use Illuminate\Support\Facades\Request;

test('isSensitive', function (string $key, bool $expected) {
    expect(new Security()->isSensitive($key))->toBe($expected);
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

test('redactIfSensitive', function (mixed $expected, string $name, mixed $value, array $sensitiveKeywords) {
    expect(new Security($sensitiveKeywords)->redactIfSensitive($name, $value))->toBe($expected);
})->with([
    ['••••••••••••••••••••', 'Name', 'test stuff craft cms', []],
    ['test stuff craft cms', 'Name', 'test stuff craft cms', ['Foo']],
    ['••••••••••••••••••••', 'Name', 'test stuff craft cms', ['Name']],
    ['••••••••••••••••••••', 'Name', 'test stuff craft cms', ['Name', 'Raaaa']],
    ['••••••••••••••••••••', 'Name Addition', 'test stuff craft cms', ['Name']],
    ['••••••••••••••••••••', 'Name Addition', 'test stuff craft cms', ['Name', 'Addition']],
    ['••••••••••••••••••••', 'not', 'test stuff craft cms', ['not', 'Naaah']],
    ['test stuff craft cms', 'naah', 'test stuff craft cms', ['not', 'naaah']],
    ['••••••••••••••••••••', 'Not', 'test stuff craft cms', ['not', 'Naaah']],
    ['••••••••••••••••••••', 'not', 'test stuff craft cms', ['Not', 'Naaah']],
    ['••••••••••••••••••••', 'not naaah', 'test stuff craft cms', ['Not', 'Naaah']],
    ['••••••••••••••••••••', 'not naaah', 'test stuff craft cms', ['not', 'naaah']],
    ['••••••••••••••••••••', 'name addition', 'test stuff craft cms', ['Name', 'Addition']],
    ['test stuff craft cms', ' ', 'test stuff craft cms', ['   ']],
    ['test stuff craft cms', '😀', 'test stuff craft cms', ['😀😘']],
    ['test stuff craft cms', '😀 😘', 'test stuff craft cms', ['😀', '😘']],
    ['••••••••••••••••••••', '😀⛄', 'test stuff craft cms', []],
    ['not stuff craft cms', '', 'not stuff craft cms', ['not']],
    ['•••••••••••••••••••', 'NOT_STUFF_CRAFT_CMS', 'not stuff craft cms', ['NOT_STUFF']],
]);

test('isSystemDir', function () {
    $configPath = config_path('craft');
    $vendorPath = base_path('vendor');
    $security = new Security;

    expect($security->isSystemDir($configPath))->toBeTrue();
    expect($security->isSystemDir($configPath.DIRECTORY_SEPARATOR.'nested'))->toBeTrue();
    expect($security->isSystemDir($vendorPath))->toBeTrue();
    expect($security->isSystemDir(base_path()))->toBeTrue();
    expect($security->isSystemDir($vendorPath.'-other'))->toBeFalse();
    expect($security->isSystemDir('/tmp/random-path'))->toBeFalse();
});

test('isRestrictedDir on Windows', function () {
    $security = new Security;
    $windowsPath = Request::server('SystemRoot') ?? 'C:\\Windows';

    expect($security->isRestrictedDir($windowsPath))->toBeTrue()
        ->and($security->isRestrictedDir($windowsPath.DIRECTORY_SEPARATOR.'System32'))->toBeTrue()
        ->and($security->isRestrictedDir($windowsPath.'-other'.DIRECTORY_SEPARATOR.'uploads'))->toBeFalse();
})->skipOnLinux()->skipOnMac();

test('custom sensitive keywords', function () {
    $security = new Security(['banana', 'apple']);

    expect($security->isSensitive('banana'))->toBeTrue();
    expect($security->isSensitive('apple'))->toBeTrue();
    expect($security->isSensitive('password'))->toBeFalse();

    expect($security->redactIfSensitive('banana', 'fruit'))->toBe('•••••');
});
