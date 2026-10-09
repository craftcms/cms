<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-laravel-extension-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir);
    File::put($this->tempDir.'/blade-partial.blade.php', 'Blade {{ $name }}');
    File::ensureDirectoryExists($this->tempDir.'/nested');
    File::put($this->tempDir.'/nested/blade-partial.blade.php', 'Nested Blade {{ $name }}');

    view()->addNamespace('laravel-extension-test', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('renders named Blade views from Twig', function () {
    $output = app(TemplateManager::class)->renderTwigString('{{ blade("laravel-extension-test::blade-partial", {"name": "Twig"}) }}');

    expect($output)->toBe('Blade Twig');
});

it('renders slash-style Blade view names from Twig', function () {
    $output = app(TemplateManager::class)->renderTwigString('{{ blade("laravel-extension-test::nested/blade-partial", {"name": "Twig"}) }}');

    expect($output)->toBe('Nested Blade Twig');
});
