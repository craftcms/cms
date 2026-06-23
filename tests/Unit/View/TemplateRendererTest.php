<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\Events\TemplateRenderersResolving;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRenderer;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Support\Facades\Event;

class TemplateRendererTestRenderer implements TemplateRendererInterface
{
    public static array $supportedFiles = [];

    public static array $renderedTemplate = [];

    public static array $renderedPageTemplate = [];

    public static string $renderTemplateOutput = '';

    public static string $renderPageTemplateOutput = '';

    public bool $isRenderingTemplate {
        get => false;
    }

    public bool $isRenderingPageTemplate {
        get => false;
    }

    public function isRenderingTemplate(): bool
    {
        return false;
    }

    public function isRenderingPageTemplate(): bool
    {
        return false;
    }

    public function supports(string $file): bool
    {
        return in_array($file, self::$supportedFiles, true);
    }

    public function renderTemplate(
        string $template,
        array $variables,
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        self::$renderedTemplate = [$template, $variables, $templateMode, $resolvedTemplate];

        return self::$renderTemplateOutput;
    }

    public function renderPageTemplate(
        string $template,
        array $variables,
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        self::$renderedPageTemplate = [$template, $variables, $templateMode, $resolvedTemplate];

        return self::$renderPageTemplateOutput;
    }

    public function renderString(string $template, array $variables, TemplateMode $templateMode = TemplateMode::Site): string
    {
        return $template;
    }
}

beforeEach(function () {
    TemplateRendererTestRenderer::$supportedFiles = [];
    TemplateRendererTestRenderer::$renderedTemplate = [];
    TemplateRendererTestRenderer::$renderedPageTemplate = [];
    TemplateRendererTestRenderer::$renderTemplateOutput = '';
    TemplateRendererTestRenderer::$renderPageTemplateOutput = '';

    Event::listen(TemplateRenderersResolving::class, function (TemplateRenderersResolving $event) {
        $event->renderers = [TemplateRendererTestRenderer::class];
    });
});

it('resolves templates using the requested template mode', function () {
    $resolver = Mockery::mock(TemplateResolver::class);

    TemplateRendererTestRenderer::$supportedFiles = ['/tmp/settings/example.blade.php'];
    TemplateRendererTestRenderer::$renderTemplateOutput = 'rendered-template';

    $resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('settings/example', TemplateMode::Cp, false)
        ->andReturn('/tmp/settings/example.blade.php');

    $renderer = new TemplateRenderer($resolver);

    expect($renderer->renderTemplate('settings/example', ['value' => 'test'], TemplateMode::Cp))
        ->toBe('rendered-template');
    expect(TemplateRendererTestRenderer::$renderedTemplate)
        ->toBe(['settings/example', ['value' => 'test'], TemplateMode::Cp, '/tmp/settings/example.blade.php']);
});

it('resolves page templates using the requested template mode and visibility', function () {
    $resolver = Mockery::mock(TemplateResolver::class);

    TemplateRendererTestRenderer::$supportedFiles = ['/tmp/articles/show.blade.php'];
    TemplateRendererTestRenderer::$renderPageTemplateOutput = 'rendered-page-template';

    $resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('articles/show', TemplateMode::Site, true)
        ->andReturn('/tmp/articles/show.blade.php');

    $renderer = new TemplateRenderer($resolver);

    expect($renderer->renderPageTemplate('articles/show', ['entry' => 'test'], TemplateMode::Site, publicOnly: true))
        ->toBe('rendered-page-template');
    expect(TemplateRendererTestRenderer::$renderedPageTemplate)
        ->toBe(['articles/show', ['entry' => 'test'], TemplateMode::Site, '/tmp/articles/show.blade.php']);
});

it('throws a template loader exception for missing templates', function () {
    $resolver = Mockery::mock(TemplateResolver::class);

    $resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('missing/template', TemplateMode::Cp, false)
        ->andReturnFalse();

    $renderer = new TemplateRenderer($resolver);

    expect(fn () => $renderer->renderTemplate('missing/template', templateMode: TemplateMode::Cp))
        ->toThrow(TemplateLoaderException::class, 'Unable to find the template');
});
