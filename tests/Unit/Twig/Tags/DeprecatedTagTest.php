<?php

declare(strict_types=1);

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\View\TemplateManager;

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

it('logs a deprecation warning and continues rendering', function () {
    $logged = false;

    $mock = Mockery::mock(Deprecator::class);
    $mock->shouldReceive('log')
        ->once()
        ->withArgs(function (string $key, string $message) use (&$logged) {
            $logged = true;

            return str_starts_with($key, 'template:') && $message === 'This feature is deprecated';
        });

    app()->scoped(Deprecator::class, fn () => $mock);

    $result = $this->manager->renderString(
        '{% deprecated "This feature is deprecated" %}Still renders',
    );

    expect($logged)->toBeTrue();
    expect(trim((string) $result))->toBe('Still renders');
});

it('continues rendering after the deprecation tag', function () {
    // Suppress the deprecation log by using a no-op mock
    $mock = Mockery::mock(Deprecator::class);
    $mock->shouldReceive('log');
    app()->scoped(Deprecator::class, fn () => $mock);

    $result = $this->manager->renderString(
        'Before {% deprecated "old" %}After',
    );

    expect(trim((string) $result))->toBe('Before After');
});
