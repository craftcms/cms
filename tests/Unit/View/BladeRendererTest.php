<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\View\BladeRenderer;
use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

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
    File::ensureDirectoryExists($this->tempDir.'/nested');
    File::put($this->tempDir.'/nested/partial.blade.php', 'Nested {{ $name }}');
    File::put($this->tempDir.'/twig-partial.twig', 'Twig {{ name }}');
    Aliases::set('@templates', $this->tempDir);
    view()->addNamespace('blade-test', $this->tempDir);
    view()->addLocation($this->tempDir);

    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('renders Blade files through the Craft page lifecycle', function () {
    $output = $this->renderer->renderPageFile($this->tempDir.'/page.blade.php', ['name' => 'Craft'], template: 'page');

    expect($output)
        ->toContain('Hello, Craft!')
        ->toContain('console.log("blade");')
        ->not->toContain('CRAFT-BLOCK-BODY-END');
});

it('dispatches neutral Blade events', function () {
    Event::fake([
        TemplateRendering::class,
        TemplateRendered::class,
        PageTemplateRendering::class,
        PageTemplateRendered::class,
    ]);

    $this->renderer->renderPageFile($this->tempDir.'/page.blade.php', ['name' => 'Craft'], template: 'page');

    Event::assertDispatched(fn (TemplateRendering $event) => $event->engine === TemplateEngine::Blade && $event->template === 'page');
    Event::assertDispatched(fn (TemplateRendered $event) => $event->engine === TemplateEngine::Blade && $event->template === 'page');
    Event::assertDispatched(fn (PageTemplateRendering $event) => $event->engine === TemplateEngine::Blade && $event->template === 'page');
    Event::assertDispatched(fn (PageTemplateRendered $event) => $event->engine === TemplateEngine::Blade && $event->template === 'page');
});

it('allows neutral events to mutate Blade variables and output', function () {
    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->variables['name'] = 'Mutated';
    });
    Event::listen(TemplateRendered::class, function (TemplateRendered $event) {
        $event->output = str_replace('Mutated', 'Rendered', $event->output);
    });

    $output = $this->renderer->renderFile($this->tempDir.'/page.blade.php', ['name' => 'Original'], template: 'page');

    expect($output)->toContain('Hello, Rendered!');
});

it('restores the template mode after rendering', function () {
    TemplateMode::set(TemplateMode::Cp);

    $this->renderer->renderFile($this->tempDir.'/page.blade.php', ['name' => 'Craft'], TemplateMode::Site, 'page');

    expect(TemplateMode::get())->toBe(TemplateMode::Cp);
});

it('renders named Laravel views', function () {
    $output = $this->renderer->renderView('blade-test::partial', ['name' => 'Blade']);

    expect($output)->toBe('Named Blade');
});

it('renders slash-style Laravel view names', function () {
    $output = $this->renderer->renderView('nested/partial', ['name' => 'Blade']);

    expect($output)->toBe('Nested Blade');
});

it('includes Twig partials from Blade', function () {
    $output = $this->renderer->renderString('@include("twig-partial", ["name" => "Blade"])');

    expect($output)->toBe('Twig Blade');
});

it('renders inline Blade templates', function () {
    expect($this->renderer->renderString('Inline {{ $name }}', ['name' => 'Blade']))
        ->toBe('Inline Blade');
});
