<?php

declare(strict_types=1);

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders the web component from the legacy variable API', function () {
    $mock = Mockery::mock(Deprecator::class);
    $mock->shouldNotReceive('log');
    app()->scoped(Deprecator::class, fn () => $mock);

    $html = renderString(
        "{% include '_includes/forms/lightswitch' with {id: 'ls', name: 'enabled', on: true, label: 'Enabled'} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContainTag('craft-switch', ['on-label' => 'Enabled'])
        ->and($html)->toContain(' checked')
        ->and($html)->toContainTag('input', ['type' => 'hidden', 'name' => 'enabled', 'value' => '1']);
});

it('logs a deprecation for the unsupported descriptionId param', function () {
    $logged = false;

    $mock = Mockery::mock(Deprecator::class);
    $mock->shouldReceive('log')
        ->once()
        ->withArgs(function (string $key, string $message) use (&$logged) {
            $logged = true;

            return str_contains($message, 'descriptionId');
        });

    app()->scoped(Deprecator::class, fn () => $mock);

    renderString(
        "{% include '_includes/forms/lightswitch' with {id: 'ls', descriptionId: 'custom-desc'} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($logged)->toBeTrue();
});
