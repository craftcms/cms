<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Foundation\Vite;
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

it('renders Vite font tags for the given families from Twig', function () {
    $this->withVite();
    $buildDirectory = 'laravel-extension-test-'.uniqid();
    File::ensureDirectoryExists(public_path($buildDirectory));
    File::put(public_path("$buildDirectory/fonts-manifest.json"), Json::encode([
        'version' => 1,
        'families' => ['inter' => [], 'mono' => []],
        'preloads' => [
            ['alias' => 'inter', 'file' => 'assets/inter.woff2', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ['alias' => 'mono', 'file' => 'assets/mono.woff2', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
        ],
        'style' => [
            'familyStyles' => ['inter' => '@font-face{font-family:Inter}', 'mono' => '@font-face{font-family:Mono}'],
        ],
    ]));
    app(Vite::class)->useBuildDirectory($buildDirectory);

    try {
        $output = app(TemplateManager::class)->renderTwigString('{{ fonts("inter") }}', escapeHtml: true);
    } finally {
        File::deleteDirectory(public_path($buildDirectory));
    }

    expect($output)->toBe(sprintf(
        "<link rel=\"preload\" as=\"font\" href=\"%s\" type=\"font/woff2\" crossorigin=\"anonymous\" />\n<style>\n@font-face{font-family:Inter}\n</style>",
        asset("$buildDirectory/assets/inter.woff2"),
    ));
});
