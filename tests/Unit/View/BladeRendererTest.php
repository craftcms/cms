<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\File as Path;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as LaravelView;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-blade-renderer-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir);
    $this->tempDir = realpath($this->tempDir) ?: $this->tempDir;

    File::put($this->tempDir.'/page.blade.php', <<<'BLADE'
<html>
<head>@craftHead</head>
<body>
@craftEndBody
@craftJs('console.log("blade");')
Hello, {{ $name }}!
</body>
</html>
BLADE);

    File::put($this->tempDir.'/partial.blade.php', 'Named {{ $name }}');
    File::ensureDirectoryExists($this->tempDir.'/index-view');
    File::put($this->tempDir.'/index-view/index.blade.php', 'Indexed {{ $name }}');
    File::ensureDirectoryExists($this->tempDir.'/nested');
    File::put($this->tempDir.'/nested/partial.blade.php', 'Nested {{ $name }}');
    File::put($this->tempDir.'/twig-partial.twig', 'Twig {{ name }}');
    Aliases::set('@templates', $this->tempDir);
    view()->addNamespace('blade-test', $this->tempDir);
    view()->addLocation($this->tempDir);

    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->manager = app(TemplateManager::class);
    $this->bladeRenderer = $this->manager->renderer(TemplateEngine::Blade);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('renders Blade files through the Craft page lifecycle', function () {
    $output = $this->manager->renderPageTemplate('page', ['name' => 'Craft'], renderer: TemplateEngine::Blade);

    expect($output)
        ->toContain('Hello, Craft!')
        ->toContain('console.log("blade");')
        ->not->toContain('CRAFT-BLOCK-BODY-END');
});

it('renders with variables mutated by template events', function () {
    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->variables['name'] = 'Mutated';
    });

    $output = $this->manager->renderTemplate('page', ['name' => 'Original'], renderer: TemplateEngine::Blade);

    expect($output)->toContain('Hello, Mutated!');
});

it('renders named Laravel views', function () {
    $output = $this->bladeRenderer->renderTemplate('blade-test::partial', ['name' => 'Blade'], TemplateMode::Site);

    expect($output)->toBe('Named Blade');
});

it('preserves logical view names for Craft-resolved Blade templates', function () {
    $viewName = null;
    $viewPath = null;

    View::composer('index-view', function (LaravelView $view) use (&$viewName, &$viewPath) {
        $viewName = $view->name();
        $viewPath = Path::normalizePath($view->getPath());
        $view->with('name', 'Composed');
    });

    $output = $this->manager->renderTemplate('index-view', ['name' => 'Original'], renderer: TemplateEngine::Blade);

    expect($output)->toBe('Indexed Composed')
        ->and($viewName)->toBe('index-view')
        ->and($viewPath)->toBe(Path::normalizePath($this->tempDir.'/index-view/index.blade.php'));
});

it('runs creators for Craft-resolved Blade templates', function () {
    View::creator('index-view', function (LaravelView $view) {
        $view->with('name', 'Created');
    });

    $output = $this->manager->renderTemplate('index-view', ['name' => 'Original'], renderer: TemplateEngine::Blade);

    expect($output)->toBe('Indexed Created');
});

it('renders slash-style Laravel view names', function () {
    $output = $this->bladeRenderer->renderTemplate('nested/partial', ['name' => 'Blade'], TemplateMode::Site);

    expect($output)->toBe('Nested Blade');
});

it('includes Twig partials from Blade', function () {
    $output = $this->manager->renderString('@include("twig-partial", ["name" => "Blade"])', renderer: TemplateEngine::Blade);

    expect($output)->toBe('Twig Blade');
});

it('renders inline Blade templates', function () {
    expect($this->manager->renderString('Inline {{ $name }}', ['name' => 'Blade'], renderer: TemplateEngine::Blade))
        ->toBe('Inline Blade');
});
