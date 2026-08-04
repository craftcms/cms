<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\Twig\Variables\Facade as FacadeVariable;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ViewErrorBag;
use Override;
use Throwable;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class LaravelExtension extends AbstractExtension implements GlobalsInterface
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('app', app(...)),
            new TwigFunction('blade', fn (string $view, array $variables = []): string => Template::renderer(TemplateEngine::Blade)
                ->renderTemplate($view, $variables, TemplateMode::get()), ['is_safe' => ['html']]),
            new TwigFunction('can', Gate::check(...)),
            new TwigFunction('config', config(...)),
            new TwigFunction('csrf_token', fn () => csrf_token(), ['is_safe' => ['html']]),
            new TwigFunction('csrf_field', fn () => csrf_field(), ['is_safe' => ['html']]),
            new TwigFunction('method_field', method_field(...), ['is_safe' => ['html']]),
            new TwigFunction('old', old(...)),
            new TwigFunction('session', session(...)),
            new TwigFunction('vite', [app(Vite::class), '__invoke'], ['is_safe' => ['html']]),
        ];
    }

    public function getGlobals(): array
    {
        try {
            $errors = request()->session()->get('errors') ?: new ViewErrorBag;
        } catch (Throwable) {
            // Session is not started yet
            $errors = new ViewErrorBag;
        }

        return array_merge([
            'errors' => $errors,
            'Auth' => new FacadeVariable(Auth::class),
        ], collect(AliasLoader::getInstance()->getAliases())
            ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Facade::class))
            ->map(fn (string $class) => new FacadeVariable($class))
            ->all()
        );
    }
}
